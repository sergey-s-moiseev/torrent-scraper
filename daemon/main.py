import multiprocessing
import socket
from http_parser.parser import HttpParser

host = "0.0.0.0"
port = 5000
psize = 1025

def scrap_result(result):
  import json
  import logging
  logging.basicConfig(level=logging.DEBUG)
  logger = logging.getLogger("result")

  logger.info(json.dumps(result, sort_keys=True))

def scrap(data):
  import scraper

  result = {}
  try:
    trackers = data.get('trackers')
    hashes = data.get('hashes')
    for tracker in trackers:
      _result = scraper.scrape(tracker,hashes)
      for _hash, _info in _result.items():
        _seeds = 0
        _peers = 0

        if _hash in result:
          _seeds = result.get(_hash).get('seeds')
          _peers = result.get(_hash).get('peers')

        result[_hash] = {'seeds': _seeds + _info.get('seeds'), 'peers': _peers + _info.get('peers')}
  except:
    scrap_result({})
  finally:
    scrap_result(result)

def handle(connection, address, queue):
  import logging
  import json

  logging.basicConfig(level=logging.DEBUG)
  logger = logging.getLogger("process-%r" % (address,))
  headers = None
  content = []
  parser = HttpParser()

  try:
    logger.debug("Connected %r at %r", connection, address)
    while True:
      _resp = connection.recv(psize)
      _recved = len(_resp)

      _parsed = parser.execute(_resp, _recved)
      assert _parsed == _recved

      if parser.is_headers_complete():
        headers = parser.get_headers()

      if parser.is_partial_body():
        content.append(parser.recv_body())

      if parser.is_message_complete():
        break
  except:
    logger.exception("Problem handling request")
  finally:
    data = None
    try:
      _json = json.loads("".join(content))
      data = _json.get('data')
    finally:
      if data is None:
        connection.send("HTTP/1.1 500 Error\n"
                        +"Content-Type: application/json\n"
                        +"\n" # Important!
                         "Empty data JSON\n")
      else:
        connection.send("HTTP/1.1 200 OK\n"
                      +"Content-Type: application/json\n"
                      +"\n" # Important!
                      "Ok\n")
        queue.put({address: data})

class Server:
  def __init__(self, hostname, port):
    import logging
    self.logger = logging.getLogger("server")
    self.hostname = hostname
    self.port = port
    self.queue = multiprocessing.Queue()
    self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    self.socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)

  def start(self):
    self.logger.debug("listening")
    self.socket.bind((self.hostname, self.port))
    self.socket.listen(1)

    while True:
      conn, address = self.socket.accept()
      self.logger.debug("Got connection")
      process = multiprocessing.Process(target=handle, args=(conn, address, self.queue))
      # process.daemon = True
      process.start()
      self.logger.debug(process)
      process.join()
      self.logger.debug(process)
      result = self.queue.get(address)
      if result.get(address):
        conn.close()
        _process = multiprocessing.Process(target=scrap, args=(result.get(address),))
        _process.start()
        _process.join()


if __name__ == "__main__":
  import logging

  logging.basicConfig(level=logging.DEBUG)
  server = Server(host, port)
  try:
    logging.info("Listening on %s:%d", host, port)
    server.start()
  except:
    logging.exception("Unexpected exception")
  finally:
    logging.info("Shutting down")
    for process in multiprocessing.active_children():
      logging.info("Shutting down process %r", process)
      process.terminate()
      process.join()
