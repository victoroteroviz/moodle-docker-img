#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color
if [ -z "$1" ]; then
    echo -e "${RED}Error: No se proporcionó el nombre del contenedor.${NC}"
    echo -e "Uso: ${YELLOW} Uso: bash deploy.sh <nombre_contenedor>${NC}"
    echo -e "Ejemplo: ${YELLOW} bash deploy.sh moodle_app${NC}"
    exit 1
fi

VERSION=$1
USUARIO=""
IMAGE_NAME="${USUARIO}/academia-okip:${VERSION}"

echo -e "${GREEN}===> Construyendo la imagen Docker<===${NC}"
echo -e "${YELLOW}Nombre clave: ${IMAGE_NAME}${NC}\n"

# Paso 1: Construir la imagen Docker
echo -e "${GREEN}Construyendo la imagen Docker...${NC}"
docker build -t ${IMAGE_NAME} .
if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Falló la construcción de la imagen Docker.${NC}"
    exit 1
fi

echo -e "${GREEN}Imagen Docker construida exitosamente: ${IMAGE_NAME}${NC}\n"

# Paso 2: Subir la imagen a Docker Hub
echo -e "${GREEN}Subiendo la imagen a Docker Hub...${NC}"
docker push ${IMAGE_NAME}
if [ $? -ne 0 ]; then
    echo -e "${RED}Error: Falló la subida de la imagen a Docker Hub.${NC}"
    exit 1
fi

echo -e "${GREEN}Imagen Docker subida exitosamente a Docker Hub: ${IMAGE_NAME}${NC}\n"
echo -e "${GREEN}Despliegue completado exitosamente.${NC}"