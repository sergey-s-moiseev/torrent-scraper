import multiprocessing
import socket
import scraper

host = "0.0.0.0"
port = 5000

def parse_response(x):
    poscl = x.lower().find('\r\ncontent-length: ')
    poseoh = x.find('\r\n\r\n')
    if poscl < poseoh and poscl >= 0 and poseoh >= 0:
        # found CL header
        poseocl = x.find('\r\n',poscl+17)
        cl = int(x[poscl+17:poseocl])
        realdata = x[poseoh+4:]

def handle(connection, address):
    import logging
    import json

    logging.basicConfig(level=logging.DEBUG)
    logger = logging.getLogger("process-%r" % (address,))
    try:
        logger.debug("Connected %r at %r", connection, address)
        while True:
            data = connection.recv(65536)
            if data == "":
                logger.debug("Socket closed remotely")
                break
            else:
                logger.debug(parse_response(data))
            # connection.sendall(data)
            # logger.debug("Sent data")
            # connection.close()
    except:
        logger.exception("Problem handling request")
    finally:
        logger.debug("Closing socket")
        connection.close()

class Server(object):
    def __init__(self, hostname, port):
        import logging
        self.logger = logging.getLogger("server")
        self.hostname = hostname
        self.port = port

    def start(self):
        self.logger.debug("listening")
        self.socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
        self.socket.bind((self.hostname, self.port))
        self.socket.listen(1)

        while True:
            conn, address = self.socket.accept()
            self.logger.debug("Got connection")
            process = multiprocessing.Process(target=handle, args=(conn, address))
            process.daemon = True
            process.start()
            self.logger.debug("Started process %r", process)

if __name__ == "__main__":
    import logging
    logging.basicConfig(level=logging.DEBUG)
    # server = Server(host, port)
    # try:
    #     logging.info("Listening on 5000")
    #     server.start()
    # except:
    #     logging.exception("Unexpected exception")
    # finally:
    #     logging.info("Shutting down")
    #     for process in multiprocessing.active_children():
    #         logging.info("Shutting down process %r", process)
    #         process.terminate()
    #         process.join()
    # logging.info("All done")


    info = scraper.api_upload("magnet:?xt=urn:btih:03633354cccd6e32c8d89efc32abc87848237d76&dn=2.Broke.Girls.S06E15.HDTV.x264-LOL%5Beztv%5D.mkv%5Beztv%5D&tr=udp%3A%2F%2Ftracker.coppersurfer.tk%3A80&tr=udp%3A%2F%2Fglotorrents.pw%3A6969%2Fannounce&tr=udp%3A%2F%2Ftracker.leechers-paradise.org%3A6969&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337%2Fannounce&tr=udp%3A%2F%2Fexodus.desync.com%3A6969")
    print("Torrent hash:", info.get('data').get('hash'))
    trackers = ['udp://tracker.coppersurfer.tk:80','udp://glotorrents.pw:6969/announce','udp://tracker.leechers-paradise.org:6969','udp://tracker.opentrackr.org:1337/announce','udp://exodus.desync.com:6969']
    logging.info(scraper.scrape_trackers(info.get('data').get('hash'), trackers))
