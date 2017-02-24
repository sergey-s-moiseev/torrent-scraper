import multiprocessing
import socket
from http_parser.parser import HttpParser

host = "0.0.0.0"
port = 5000
psize = 1025

def scrap(content):
  import json
  import logging

  import scraper

  logging.basicConfig(level=logging.DEBUG)
  logger = logging.getLogger("scraper")

  _json = json.loads("".join(content))
  try:
    data = _json.get('data')
    trackers = data.get('trackers')
    hashes = data.get('hashes')
    for tracker in trackers:
      logger.debug(scraper.scrape(tracker,hashes))
  except KeyError:
    logger.exception("Wrong JSON received")


def handle(connection, address, queue):
  import logging

  logging.basicConfig(level=logging.DEBUG)
  logger = logging.getLogger("process-%r" % (address,))
  headers = []
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
        headers.append(parser.get_headers())

      if parser.is_partial_body():
        content.append(parser.recv_body())

      if parser.is_message_complete():
        break
  except:
    logger.exception("Problem handling request")
  finally:
    scrap(content)
    connection.send("HTTP/1.1 200 OK\n"
                    +"Content-Type: text/html\n"
                    +"\n" # Important!
                    +"Ok\n")


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
      conn.close()

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
