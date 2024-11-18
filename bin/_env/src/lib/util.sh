# Loads the script environment file or dies if it does not exist
loadEnvFile(){
  ENV_FILE=${ENV_FILE:-"${PROJECT_ROOT_DIR}/.env"}

  if [ ! -f ${ENV_FILE} ]; then
    echo "Missing ${ENV_FILE} file! Please copy .env.tpl, rename it to .env and add the required values before continuing!";
    exit 1;
  fi

  source ${ENV_FILE}
}

# Asserts that the provided command exist, or kills the script
assertCommandExists(){
  MESSAGE=${2:-"Error: the required executable ${1} does not exist"}
  if ! [ -x "$(command -v $1)" ]; then
    echo "${MESSAGE}" >&2
    exit 1
  fi
}

# Asks the user for confirmation and assumes yes should be the default answer
confirmDefaultYes(){
  QUESTION=${1:-"Are you sure?"}
  read -r -p "${QUESTION} [Y/n] " RESPONSE
  RESPONSE=${RESPONSE,,}

  if [[ "${RESPONSE}" =~ ^(yes|y)$ ]] || [[ ${RESPONSE} == "" ]]; then
    return
  fi

  false
}

# Asks the user for confirmation and assumes no should be the default answer
confirmDefaultNo(){
  QUESTION=${1:-"Are you sure?"}
  read -r -p "${QUESTION} [y/N] " RESPONSE
  RESPONSE=${RESPONSE,,}

  if [[ "${RESPONSE}" =~ ^(no|n)$ ]] || [[ ${RESPONSE} == "" ]]; then
  	false
    return
  fi
}

# Checks the last exit code and kills the script if it is bigger than 0
checkLastExitCodeOrDie(){
  CODE=${1:-$?}
  MESSAGE=${2:-"There was an issue with running a task"}

  if [ ${CODE} -ne 0 ]
  then
    echo "${MESSAGE}" >&2
    exit 1
  fi
}

determineHostType() {
  OS='unsupported';
  case "$OSTYPE" in
    #darwin*)  OS="OSX" ;;
    linux*)   OS="LINUX" ;;
    msys*)    OS="WINDOWS" ;;
    cygwin*)  OS="WINDOWS" ;;
  esac

  echo $OS
  return
}

determineOsPlatform() {
  if [[ $OS_TYPE == 'LINUX' ]]; then
    echo $(dpkg --print-architecture)
    return
  fi

  if [[ $OS_TYPE == 'WINDOWS' ]]; then
    echo %PROCESSOR_ARCHITECTURE%
    return
  fi
}

isWslMachine() {
  if [[ $(grep Microsoft /proc/version) ]]; then
    return
  fi
  if [[ $(grep microsoft /proc/version) ]]; then
    return
  fi

  false
}
