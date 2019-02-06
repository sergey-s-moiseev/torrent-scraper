import scraplog
import multiprocessing
import socket
from http_parser.parser import HttpParser
import sys
import time
import signal
import os

psize = 1025
started = time.time()

def scrap_result(result, url, key, is_ssl = False):
  import logging
  import urllib
  import http.client
  import json

  logging.basicConfig(level=logging.DEBUG)
  logger = logging.getLogger("result")

  headers = {"Content-type": "application/json"}
  serv = url.split("/")
  conn = http.client.HTTPSConnection(serv[0]) if is_ssl else http.client.HTTPConnection(serv[0])

  params = json.dumps({'data': result, 'key': key}, sort_keys=True)
  del serv[0]
  try:
    conn.request("POST", ("/%s" % ('/'.join(serv))), params, headers)
    response = conn.getresponse()
    logger.info(response.read())
  except:
    logger.exception("Problem handling request: %s", sys.exc_info()[1])
  finally:
    conn.close()

def scrap(db_path, data, url, key):
  import scraper
  import logging

  logger = logging.getLogger("scrap-%r" % key)

  result = {}
  is_ssl = False

  logger.debug("Proccess for %s" % url)

  if url[:5] == 'https':
    url = url[8:]
    is_ssl = True
  elif url[:5] == 'http:':
    url = url[7:]

  trackers = data.get('trackers')
  hashes = data.get('hashes')
  scrap_log = scraplog.ScrapLog(config.get('db_path'))
  scrap_log.start_logging(key, url, hashes)

  try:
    _cur = 0
    for tracker in trackers:
      _cur += 1
      _result = scraper.scrape(tracker,hashes, [_cur, len(trackers)])
      scrap_log.add_row(tracker, _result)
      for _hash, _info in _result.items():
        _seeds = 0
        _peers = 0

        if _hash in result:
          _seeds = result.get(_hash).get('seeds')
          _peers = result.get(_hash).get('peers')

        result[_hash] = {'seeds': _seeds + _info.get('seeds'), 'peers': _peers + _info.get('peers')}
  except Exception as e:
    scrap_result(str(e), url, key, is_ssl)
    scrap_log.add_error(e)
  finally:
    scrap_result(result, url, key, is_ssl)
    scrap_log.stop_logging()
    scrap_log.close()

def send_and_close(connection, code = 200, data = {}):
  import json

  body = json.dumps(data) if len(data) > 0 else ''
  body += "\n"
  body_length = len(body)
  content_type = 'application/json' if body_length > 0 else 'text/text'
  if code == 200:
    http_code = '200 OK'
  elif code == 400:
    http_code = '400 Bad Request'
  elif code == 429:
    http_code = '429 Too Many Requests'
  else:
    http_code = '500 Internal Server Error'
  response = "HTTP/1.1 %s\nContent-Type: %s\nContent-Length: %d\n\n%s" % (http_code, content_type, body_length, body)
  connection.send(bytes(response, 'utf-8'))
  connection.shutdown(socket.SHUT_RDWR)
  connection.close()

def handle(connection, address, pid, queue_obj, db_path):
  import logging
  import json
  from queue import Full

  logging.basicConfig(level=logging.DEBUG)
  logger = logging.getLogger("process-%r" % (address,))
  content = []
  parser = HttpParser()

  try:
    logger.debug("Connected %r at %r", connection, address)
    while True:
      resp = connection.recv(psize)
      recved = len(resp)

      parsed = parser.execute(resp, recved)
      assert parsed == recved

      if parser.is_headers_complete():
        parser.get_headers()

      if parser.is_partial_body():
        content.append(parser.recv_body())

      if parser.is_message_complete():
        break
  except:
    logger.exception("Problem handling request: %s", sys.exc_info()[1])
    send_and_close(connection, 500)
    return

  parsed_json = {}
  data = None

  try:
    parsed_json = json.loads("".join(map(lambda s: s.decode("utf-8"), content)))
    data = parsed_json.get('data')
    url = parsed_json.get('callback')
    key = parsed_json.get('private_key')
  except:
    logger.exception("Problem decoding JSON: %s", sys.exc_info()[1])
  finally:
    if data is None:
      send_and_close(connection, 400, {"message": "JSON Parse Error"})
    elif data == 'ping':
      send_and_close(connection, 200, {"started": started, "queue": queue_obj.qsize()})
    elif data == 'stop':
      send_and_close(connection, 200, {"message": "Shutting down"})
      os.kill(pid, signal.SIGUSR1)
    elif 'interval' in data:
      interval = data.get('interval')
      scrap_log = scraplog.ScrapLog(db_path)
      logs = scrap_log.get_logs(interval[0],interval[1])
      scrap_log.close()
      send_and_close(connection, 200, logs)
    elif 'trackers' in data and 'hashes' in data:
      try:
        queue_obj.put({"data": [data, url, key], "address": address}, False)
        send_and_close(connection, 200, {"message": ("in queue [%r]" % (address,))})
      except Full:
        send_and_close(connection, 429, {"message": "Server queue is full. Try another one."})

def process_queue(queue_obj, config):
  from queue import Empty
  pool = multiprocessing.Pool(processes = config.get('processes'), maxtasksperchild = 100)

  while True:
    time.sleep(0.5)
    try:
      data = queue_obj.get(False)
      pool.apply_async(func=scrap, args=[config.get('db_path')] + data.get("data"))
      # multiprocessing.Process(target=scrap, args=[config.get('db_path')] + data.get("data")).start()
    except Empty:
      continue

class Server:
  def __init__(self, config):
    import logging
    self.logger = logging.getLogger("server")
    self.config = config
    self.queue = multiprocessing.Queue(maxsize = config.get('queue_limit'))
    self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    self.socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    self.socket.setblocking(0)
    self.socket.settimeout(5.0)
    multiprocessing.Process(target=process_queue, args=(self.queue, config)).start()

  def start(self):
    self.logger.debug('Listening')
    self.socket.bind((config.get('host'), config.get('port')))
    self.socket.listen()

    while True:
      try:
        connection, address = self.socket.accept()
        connection.setblocking(0)
      except socket.error: 
        continue
      else:
        self.logger.debug("Got connection")
        process = multiprocessing.Process(target=handle, args=(connection, address, os.getpid(), self.queue, self.config.get('db_path')))
        process.start()

class ExitCommand(Exception):
  pass

def signal_handler(signal, frame):
  raise ExitCommand()

if __name__ == "__main__":
  import argparse
  import logging

  parser = argparse.ArgumentParser(prog='scraper')
  parser.add_argument('--db', help='Database path')
  parser.add_argument('--host', default='0.0.0.0', help='Host address to bind to')
  parser.add_argument('--port', default='5000', help='Port to bind to')
  parser.add_argument('--processes', default=multiprocessing.cpu_count(), help='Number of scrapper processes')
  parser.add_argument('--queue-limit', default=100, help='Scrapper queue limit')
  args = parser.parse_args()
  config = {'host': args.host, 'port': int(args.port), 'db_path': args.db, 'processes': int(args.processes), 'queue_limit': int(args.queue_limit)}

  logging.basicConfig(level=logging.DEBUG)
  server = Server(config)
  signal.signal(signal.SIGUSR1, signal_handler)

  scrap_log = scraplog.ScrapLog(config.get('db_path'))
  scrap_log.check_tables()
  scrap_log.close()

  try:
    logging.info("Listening on %s:%d", config.get('host'), config.get('port'))
    server.start()
  except ExitCommand:
    pass
  except:
    logging.error("Unexpected exception: %s", sys.exc_info()[1])
  finally:
    logging.info("Shutting down")
    for process in multiprocessing.active_children():
      logging.info("Shutting down process %r", process)
      process.terminate()
      process.join()
