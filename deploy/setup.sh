#!/bin/sh
set -eu

cd -- "$(dirname -- "$0")/.."
python3 - <<'PY'
import json
import os
from pathlib import Path
import re
import secrets
import subprocess

environment = Path(".env")
if not environment.exists():
    template = Path(".env.example").read_text()
    template = template.replace("GENERATE_DB_PASSWORD", secrets.token_hex(24))
    template = template.replace("GENERATE_JWT_SECRET", secrets.token_urlsafe(48))
    descriptor = os.open(environment, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    with os.fdopen(descriptor, "w") as stream:
        stream.write(template)
    print(".env aangemaakt met willekeurige database- en JWT-geheimen.")

config = json.loads(subprocess.check_output(
    ["docker", "compose", "config", "--format", "json"], text=True
))
project = config["name"]
if project != "cards" or not re.fullmatch(r"[a-z][a-z0-9-]*", project):
    raise SystemExit("Ongeldige Compose-projectnaam.")

environment_values = config["services"]["cards-webserver"]["environment"]
for key in ("DB_PASSWORD", "JWT_SECRET"):
    value = environment_values.get(key, "")
    if not value or value.startswith("GENERATE_"):
        raise SystemExit(f"Vervang de placeholder voor {key} via deploy/setup.sh.")

for identifier in subprocess.check_output([
    "docker", "ps", "-aq", "--filter", f"label=com.docker.compose.project={project}"
], text=True).split():
    details = json.loads(subprocess.check_output(
        ["docker", "inspect", identifier], text=True
    ))[0]
    directory = details["Config"]["Labels"].get("com.docker.compose.project.working_dir")
    if directory != str(Path.cwd()):
        raise SystemExit("Compose-projectnaam is al in gebruik door een andere map.")

data_root = Path.cwd() / "data"
if data_root.is_symlink():
    raise SystemExit("De projectdatamap mag geen symlink zijn.")
data_root.mkdir(exist_ok=True)

for key, directory_name in {"database_data": "mysql", "logos": "logos"}.items():
    directory = data_root / directory_name
    if directory.is_symlink():
        raise SystemExit(f"Datadirectory {directory} mag geen symlink zijn.")
    directory.mkdir(exist_ok=True)
    options = {"type": "none", "o": "bind", "device": str(directory)}
    volume = config["volumes"][key]
    name = volume["name"]
    result = subprocess.run(
        ["docker", "volume", "inspect", name], capture_output=True, text=True
    )
    if result.returncode == 0:
        details = json.loads(result.stdout)[0]
        labels = details.get("Labels") or {}
        if labels.get("beun.cards.owner") != str(Path.cwd()):
            raise SystemExit(f"Volume {name} bestaat al en is niet van deze checkout.")
        if details["Driver"] != "local" or details.get("Options") != options:
            raise SystemExit(f"Volume {name} verwijst niet naar de verwachte datamap.")
    else:
        command = ["docker", "volume", "create", "--driver", "local"]
        for option, value in options.items():
            command.extend(["--opt", f"{option}={value}"])
        command.extend(["--label", f"beun.cards.owner={Path.cwd()}", name])
        subprocess.run(command, check=True, stdout=subprocess.DEVNULL)
    print(f"Cards-volume gereed: {name} -> {directory}")
PY
