#!/usr/bin/env bash

set -eu

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../.." && pwd )"

i=0
TIMEOUT=30

WEB_STATUS=1

ACCOUNT=appropriate
REPO=curl
VERSION=edge

. get_colors
. "$ROOT"/bin/lib/env.sh
. get_tty

# Checks the status of the app, db and web services
checkServices() {
  # Disabled exit-on-error because we're using nc's failure to detect if services are up yet
  set +e

  "$ROOT"/bin/nc -z web 80 &> /dev/null
  WEB_STATUS=$?

  set -e
}

# Prints the status of service named $1 based on the status code of $2
printServiceStatus() {
  NAME=$1
  STATUS=$2

  if [ "$STATUS" = 0 ]; then
    echo -ne "${GREEN}${NAME}${NC} "
  else
    echo -ne "${RED}${NAME}${NC} "
  fi
}

# Prints the status of the app, db and web services
printServicesStatus() {
    echo -ne "\r"

    printServiceStatus Web ${WEB_STATUS}

    if [ "$i" -ne "0" ]; then
        for ii in `seq 1 ${i}`; do
          echo -n '.'
        done
    fi
}

echo "Waiting for Services:"
printServicesStatus

checkServices
printServicesStatus

while [ -o "$WEB_STATUS" != 0 ]; do
  sleep 1s
  i=$(expr $i + 1)

  checkServices
  printServicesStatus

  if [ "$i" -gt "$TIMEOUT" ]; then
    break
  fi
done

echo

if [ -o "$WEB_STATUS" != 0 ]; then
  echo -e "${RED}Timeout expired while waiting for services to start.${NC}"
  exit 1;
fi

echo -e "${GREEN}Services are now available.${NC}"
