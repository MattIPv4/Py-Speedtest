import datetime, pip, platform

# Import/Install requests
try:
    __import__('requests')
except ImportError:
    pip.main(['install', 'requests'])
finally:
    import requests

# Import/Install speedtest-cli
try:
    __import__('speedtest')
except ImportError:
    pip.main(['install', 'speedtest-cli'])
finally:
    import speedtest

# Begin speed test
print("Commencing speedtest")
st = speedtest.Speedtest()
st.get_servers([])
st.get_best_server()
st.download()
st.upload()

# Get results
results = st.results.dict()
upload = results['upload']
download = results['download']
ping = results['ping']

# Print data
print("Device: {}\nUpload: {}\nDownload: {}\nPing: {}\n".format(
    platform.node(),
    upload,
    download,
    ping
))

# Create new data
d = datetime.datetime.now().strftime("%Y%m%d%H%M%S")
newdata = {'device': platform.node(), 'datetime': d, 'ping': ping, 'download': download, 'upload': upload}

# Post data
r = requests.post('https://cdn.unreal-designs.co.uk/cont/pyspeedtest/datareturn.php', data = newdata)
print(r.text)
