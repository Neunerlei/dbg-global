determineDockerExecutable(){
  local dockerExecutable=$(which docker)
  if [[ ${dockerExecutable} ]]; then
    echo ${dockerExecutable}
    return
  fi
  local podmanExecutable=$(which podman)
  if [[ ${podmanExecutable} ]]; then
    echo ${podmanExecutable}
    return
  fi
  echo "Sorry, but I did not find docker or podman on your system" >&2
  exit 1
}

determineDockerComposeExecutable() {
  # Special switch for pod-man
  PODMAN_PATH=$(which podman-compose)
	if [[ ${PODMAN_PATH} ]]; then
		echo ${PODMAN_PATH}
		return
	fi
  PODMAN_PATH=$(which podman)
	if [[ ${PODMAN_PATH} ]]; then
		echo ${PODMAN_PATH} compose
		return
	fi

	COMPOSE_PATH=$(which docker-compose)

	# Check if some WSL weirdness is going on
	if [[ ${COMPOSE_PATH} ]] && [[ ${COMPOSE_PATH} != /mnt/* ]]; then
		# No wsl weirdness is going on -> return the path as is...
		echo ${COMPOSE_PATH}
		return
	fi

	COMPOSE_VERSION=$(docker compose version)

	if [[ ${COMPOSE_VERSION} == *v2* ]]; then
		echo "docker compose"
		return
	fi

  echo "Sorry, but I did not find docker-compose or 'docker compose' on your system" >&2
  exit 1
}

determineDockerRuntimeType(){
  local composeExecutable=$(determineDockerComposeExecutable)
  if [[ ${composeExecutable} == *podman* ]]; then
    echo "podman"
    return
  fi
  echo "docker"
}

provideDockerEnvironmentVariablesBasedOnRuntimeType(){
  echo "export BUILDKIT_PROGRESS=plain"
  echo "export COMPOSE_DOCKER_CLI_BUILD=1"
  echo "export DOCKER_BUILDKIT=1"

  if [[ ${DOCKER_RUNTIME_TYPE} == "podman" ]]; then
    echo "export DOCKER_RUNTIME=podman"
    echo "export DOCKER_USER=root"
  else
    echo "export DOCKER_RUNTIME=docker"
    echo "export DOCKER_USER=${DEFAULT_UID}:${DEFAULT_GID}"
    echo "export DOCKER_UID=${DEFAULT_UID}"
    echo "export DOCKER_GID=${DEFAULT_GID}"
  fi
}

isDockerContainerRunning(){
  local containerName=${1:-${DEFAULT_CONTAINER_NAME}}
  local containerId=$($DOCKER_EXECUTABLE ps -q -f name=${containerName})
  if [[ ${containerId} ]]; then
    return 0
  fi
  return 1
}
