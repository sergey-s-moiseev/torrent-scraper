import socket
import scraper


def Main():
    host = "" #""127.0.0.1"
    port = 5000

    sock = socket.socket()
    sock.bind((host,port))

    sock.listen(1)
    conn, addr = sock.accept()

    print ("Connection from: " + str(addr))

    while True:
        data = conn.recv(1024).decode()
        if not data:
            break
        print ("from connected  user: " + str(data))

        data = str(data).upper()
        print ("sending: " + str(data))
        conn.send(data.encode())

    conn.close()

if __name__ == '__main__':
    Main()