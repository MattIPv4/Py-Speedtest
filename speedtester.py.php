"""<?php
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="speedtester.py"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
?>"""
from datetime import datetime; from pip import main as pipmain; from platform import node; from base64 import b64decode

# Import/Install requests
try:
    from requests import post
except:
    pipmain(['install', 'requests'])
finally:
    from requests import post

# Import/Install speedtest-cli
try:
    import speedtest
except:
    pipmain(['install', 'speedtest-cli'])
finally:
    import speedtest

# Megabits
def megabits(number):
    return "{:,.1f} Mbps".format(number / 1000000)

# Megabyes
def megabytes(number):
    return "{:,.1f} MB/s".format((number / 1000000) / 8)

# Milliseconds
def milliseconds(number):
    return "{:,.2f} ms".format(number)

# Begin speed test
attempts = 5
print("Commencing speedtest with {:,} attempts".format(attempts))
upload = []
download = []
ping = []

# Get servers
st = speedtest.Speedtest()
servers = [f['id'] for f in st.get_closest_servers(limit=attempts)]

for attempt in range(attempts):
    # Run test
    st = speedtest.Speedtest()
    st.get_servers([servers[attempt]])
    st.get_best_server()
    print("Speedtest {:,} | ID: {}, Name: {}, Sponsor: {}".format(
        attempt, st.best['id'], st.best['name'], st.best['sponsor']))
    st.download()
    st.upload()

    # Get results
    results = st.results.dict()
    print("   Upload: {} / {}\n   Download: {} / {}\n   Ping: {}".format(
        megabits(results['upload']), megabytes(results['upload']),
        megabits(results['download']), megabytes(results['download']),
        milliseconds(results['ping'])))
    upload.append(results['upload'])
    download.append(results['download'])
    ping.append(results['ping'])


# Compile results
upload = sum(upload) / len(upload)
download = sum(download) / len(download)
ping = sum(ping) / len(ping)

# Print data
print("Device: '{}'\n"
      "Upload: {} / {}\n"
      "Download: {} / {}\n"
      "Ping: {}\n".format(
    node().strip(),
    megabits(upload), megabytes(upload),
    megabits(download), megabytes(download),
    milliseconds(ping)
))

# Create new data
d = datetime.utcnow().strftime("%Y%m%d%H%M%S")
newdata = {'device': node().strip(), 'datetime': d, 'ping': ping, 'download': download, 'upload': upload}

# Post data
r = post(b64decode('<?php echo base64_encode((isset($_SERVER["HTTPS"]) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . "/datareturn.php"); ?>'), data = newdata)
print("Data Upload: {}".format(r.text))
"""<?php die("\"\"\""); ?>"""