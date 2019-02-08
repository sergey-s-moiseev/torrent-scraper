import sqlite3
import json

class ScrapLog:
  def __init__(self, arg_path):
    import os
    import sys
    import logging

    logging.basicConfig(level=logging.DEBUG)
    self.logger = logging.getLogger("scraplog")
    path = os.path.dirname(os.path.realpath(__file__)) if arg_path is None else arg_path

    self.conn = sqlite3.connect('%s/scraplog.db' % path) #, timeout=60)
    self.conn.execute("PRAGMA journal_mode=WAL") # to share database file between threads

  def check_tables(self):
    self.logger.info("Checking tables")
    c = self.conn.cursor()

    c.execute('''CREATE TABLE IF NOT EXISTS scrap_logs
                 (id  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                 tracker varchar(255) NOT NULL,
                 hashes text NOT NULL,
                 result varchar(255) NOT NULL,
                 api_key varchar(255) NOT NULL,
                 callback_url varchar(255) NOT NULL,
                 created datetime DEFAULT current_timestamp)''')

    c.execute('''CREATE TABLE IF NOT EXISTS scrap_errors
                 (id  INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                 hashes text NOT NULL,
                 result varchar(255) NOT NULL,
                 api_key varchar(255) NOT NULL,
                 callback_url varchar(255) NOT NULL,
                 created datetime DEFAULT current_timestamp)''')

    self.logger.info(self.conn.commit())
    self.conn.commit()

  def start_logging (self, api_key, callback_url,hashes):
    self.logger.info("Start logging")

    self.api_key = api_key
    self.hashes = hashes
    self.callback_url = callback_url

  def add_row(self, tracker, result):
    cursor = self.conn.cursor()
    query = "INSERT INTO scrap_logs (tracker, hashes, result, api_key, callback_url) VALUES ('%s','%s','%s','%s','%s')"%\
                                    (tracker, json.dumps(self.hashes), json.dumps(result), self.api_key, self.callback_url)

    cursor.execute(query)

  def add_error(self, result):
    cursor = self.conn.cursor()
    query = "INSERT INTO scrap_errors (hashes, result, api_key, callback_url) VALUES ('%s','%s','%s','%s')"% \
                                      (json.dumps(self.hashes), result, self.api_key, self.callback_url)

    cursor.execute(query)


  def stop_logging(self):
    self.logger.info("Stop logging")
    self.conn.commit()
    self.conn.close()

  def get_logs(self, from_date = None, to_date = None):
    import datetime
    today = datetime.date.today()
    if from_date is None:
      from_date = today
    if to_date is None:
      to_date = today

    cursor = self.conn.cursor()
    # if from_date == to_date:
    #   query = "SELECT * FROM scrap_logs WHERE date(created) = '%s'"%(from_date)
    # else:
    #   query = "SELECT * FROM scrap_logs WHERE date(created) >= '%s' AND date(created) <= '%s'"%(from_date, to_date)

    query = "SELECT * FROM scrap_logs"
    cursor.execute(query)

    self.logger.info(query)
    self.logger.info(self.conn)
    result = cursor.fetchall()
    return result

  def close(self):
    self.logger.info("Break Logger")
    self.conn.close()

