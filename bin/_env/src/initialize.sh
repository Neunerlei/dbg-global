PROJECT_ROOT_DIR=$(realpath ${BASH_SOURCE%/*}/..)

OS_TYPE=$(determineHostType)

if [[ $OS_TYPE == 'unsupported' ]]; then
  echo 'Sorry, but we currently don''t support your operating system!'
  exit 1
fi

OS_PLATFORM=$(determineOsPlatform)

loadEnvFile

DOCKER_EXECUTABLE=$(determineDockerExecutable)
DOCKER_COMPOSE_EXECUTABLE=$(determineDockerComposeExecutable)
DOCKER_RUNTIME_TYPE=$(determineDockerRuntimeType)

DEFAULT_SERVICE_NAME=${SERVICE_NAME:-app}
DEFAULT_CONTAINER_NAME="${PROJECT_NAME:-project-without-name}-${DEFAULT_SERVICE_NAME}"
DEFAULT_UID=${ENV_UID:-$(id -u)}
DEFAULT_GID=${ENV_GID:-$(id -g)}

$(provideDockerEnvironmentVariablesBasedOnRuntimeType)
