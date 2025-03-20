# import requests

# proxies = {
#   "http": "http://vpn:vpn@public-vpn-251.opengw.net",
#   "https": "https://vpn:vpn@public-vpn-251.opengw.net",
# }

# requests.get("http://example.org", proxies=proxies)


# import requests

# data = requests.get("http://mhiptv.info:2095/live/giro069/2243768906/22.m3u8")


# print(data.text)


# pip install zenrows
# from zenrows import ZenRowsClient

# client = ZenRowsClient("b174885358e2fb22c03b2c31ec1e5f3da6454eba")
# url = "http://mhiptv.info:2095/live/giro069/2243768906/22.m3u8"
# params = {"premium_proxy":"true","proxy_country":"ca"}

# response = client.get(url, params=params)

# print(response.text)