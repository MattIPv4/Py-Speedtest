<?php
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="speedtester.py"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
?>
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

# Begin speed test
print("Commencing speedtest")
attempts = 5
upload = []
download = []
ping = []

for attempt in attempts:
    # Run test
    st = speedtest.Speedtest()
    st.get_servers([])
    st.get_best_server()
    st.download()
    st.upload()

    # Get results
    results = st.results.dict()
    upload.append(results['upload'])
    download.append(results['download'])
    ping.append(results['ping'])


# Compile results
upload = sum(upload) / len(upload)
download = sum(download) / len(download)
ping = sum(ping) / len(ping)

# Print data
print("Device: '{}'\nUpload: {}\nDownload: {}\nPing: {}\n".format(
    node().strip(),
    upload,
    download,
    ping
))

# Create new data
d = datetime.utcnow().strftime("%Y%m%d%H%M%S")
newdata = {'device': node().strip(), 'datetime': d, 'ping': ping, 'download': download, 'upload': upload}

# Post data
r = post(b64decode('<?php echo base64_encode((isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . "/datareturn.php"); ?>'), data = newdata)
print("Data Upload: {}".format(r.text))
<?php die(); ?>