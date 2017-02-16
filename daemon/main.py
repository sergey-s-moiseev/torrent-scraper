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
    server = Server(host, port)
    try:
        logging.info("Listening on 5000")
        server.start()
    except:
        logging.exception("Unexpected exception")
    finally:
        logging.info("Shutting down")
        for process in multiprocessing.active_children():
            logging.info("Shutting down process %r", process)
            process.terminate()
            process.join()
    logging.info("All done")