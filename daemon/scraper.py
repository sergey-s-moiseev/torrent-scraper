from bottle import route, request, response, default_app, HTTPError
from _scraper import scrape

import re, socket, thread, tempfile, shutil, time, os, pprint, atexit, base64, urllib
import libtorrent as lt, logging as logger, StringIO

import btdht
import binascii

get_url = default_app().get_url

api_error = lambda message: {"success" : False, "message" : message }
api_success = lambda x: {"success" : True, "data" : x }

hash_pattern = re.compile("([a-fA-F0-9]{40})")
socket.setdefaulttimeout(5)

libtorrent_settings_file = os.path.abspath("./libtorrent.settings")

def is_hash(item):
    return hash_pattern.match(item)

def is_base64(item):
    return (len(item) % 4 == 0) and re.match("^[A-Za-z0-9+/]+[=]{0,2}$", item)

def is_magnet(item):
    return item.startswith("magnet:")

def api_upload(magnet_url_or_hash=None):
    url = magnet_url_or_hash if magnet_url_or_hash else request.params.get("magnet_url_or_hash")
    if not url:
        return api_error("No magnet, url or hash supplied")
    item = url.strip()
    info_hash = "";
    try :
        if is_hash(item):
            info_hash = item;
        elif is_base64(item):
            decoded = base64.b64decode(item)
            info = lt.torrent_info(lt.bdecode(decoded))
            info_hash = "%s" % info.info_hash()

            thread.start_new_thread(add_from_torrent_info, (info, torrent_data))
        elif is_magnet(item):
            params = lt.parse_magnet_uri(item)
            info_hash = str(params["info_hash"])
            if not info_hash.replace("0", ""):
                raise RuntimeError("The hash was all 0's, did you urlencode the magnet link properly?")
        elif is_url(item):
            item = urllib.unquote_plus(item)
            logger.debug("Fetching %s" % item)
            download_to = tempfile.mkstemp(suffix=".torrent")[1]
            urllib.urlretrieve(item, download_to)
            handle = open(download_to, "rb");
            torrent_data = handle.read()
            info = lt.torrent_info(lt.bdecode(torrent_data))
            handle.close()
            os.remove(download_to)
            info_hash = "%s" % info.info_hash()
            thread.start_new_thread(add_from_torrent_info, (info, torrent_data))
        else:
            raise RuntimeError("Cannot recognise this url: %s" % item)
    except (RuntimeError, IOError) as e:
        return api_error(str(e))

    return api_success({ "url" : item, "hash" : info_hash, "added" : True})

def scrape_trackers(hash, tracker_list):
    for url in tracker_list:
        try:
            result = scrape(url, [hash])
            if (result == None):
                getDHT(hash)
                break
            for hash, stats in result.iteritems():
                print ("<<<<<<<", hash, stats)
        except (RuntimeError, NameError, ValueError, socket.timeout) as e:
            print (e)
    getDHT(hash)

def getDHT(hash):
    dht = btdht.DHT()
    dht.start()
    dht.get_peers(binascii.a2b_hex(hash))

pass