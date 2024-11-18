#!/bin/bash

# Wrapper to run the bashly application inside a docker container @see https://github.com/DannyBen/bashly
VOLUME_PATH=$(realpath ./${BASH_SOURCE%/*}/..)
docker run --rm -it --user $(id -u):$(id -g) --volume "$VOLUME_PATH:/app" --env BASHLY_SOURCE_DIR=_env/src dannyben/bashly $@