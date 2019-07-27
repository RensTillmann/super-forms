#!/bin/bash
shopt -s extglob
rm -rf src/uploads/php/files/!(.htaccess)
mkdir -p dist/super-forms dist/super-forms-bundle