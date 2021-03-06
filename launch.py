import subprocess
import os
import shutil
import re

CONFIG_FILE = os.environ["ARMA_CONFIG"]
KEYS = "/arma3/keys"

if not os.path.exists(KEYS) or not os.path.isdir(KEYS):
    if os.path.exists(KEYS):
        os.remove(KEYS)
    os.makedirs(KEYS)

subprocess.call(["/steamcmd/steamcmd.sh", "+login", os.environ["STEAM_USER"], os.environ["STEAM_PASSWORD"], "+force_install_dir", "/arma3", "+app_update", "233780", "validate", "+quit"])

def mods(d):
    launch = "\""
    mods = [os.path.join(d,o) for o in os.listdir(d) if os.path.isdir(os.path.join(d,o))]
    for m in mods:
        launch += m+";"
        keysdir = os.path.join(m,"keys")
        if os.path.exists(keysdir):
            keys = [os.path.join(keysdir,o) for o in os.listdir(keysdir) if os.path.isdir(os.path.join(keysdir,o)) == False]
            for k in keys:
                shutil.copy2(k, KEYS)
        else:
            print("Missing keys:", keysdir)
    return launch+"\""

launch = "./arma3server -mod={} -world={} {}".format(mods('mods'), os.environ["ARMA_WORLD"], os.environ["ARMA_PARAMS"])

clients = int(os.environ["HEADLESS_CLIENTS"])

print("Headless Clients:", clients)

if clients != 0:
    with open("/arma3/configs/{}".format(CONFIG_FILE)) as config:
        data = config.read()
        regex = r"(.+?)(?:\s+)?=(?:\s+)?(.+?)(?:$|\/|;)"

        config_values = {}

        matches = re.finditer(regex, data, re.MULTILINE)
        for matchNum, match in enumerate(matches, start=1):
            config_values[match.group(1).lower()] = match.group(2)

        launch += " -config=\"/arma3/configs/{}\"".format(CONFIG_FILE)

    client_launch = launch
    client_launch += " -client -connect=127.0.0.1"

    if "password" in config_values:
        client_launch += " -password={}".format(config_values["password"])

    for i in range(0, clients):
        print("LAUNCHING ARMA CLIENT {} WITH".format(i), client_launch)
        subprocess.Popen(client_launch, shell=True)

else:
    launch += " -config=\"/arma3/configs/{}\"".format(CONFIG_FILE)

launch += " -name=\"{}\" -profiles=\"/arma3/configs/profiles\"".format(os.environ["ARMA_PROFILE"])

if os.path.exists("servermods"):
    launch += " -serverMod={}".format(mods("servermods"))

print("LAUNCHING ARMA SERVER WITH", launch, flush=True)
os.system(launch)
