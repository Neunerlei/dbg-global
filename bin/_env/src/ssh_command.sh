if ! isDockerContainerRunning; then
  run up
fi

$DOCKER_EXECUTABLE exec -ti ${DEFAULT_CONTAINER_NAME} ${args[--cmd]:-bash}
