#!/bin/bash

service supervisor start > /dev/null

exec /entrypoint.sh

