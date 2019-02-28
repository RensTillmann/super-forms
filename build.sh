#!/bin/bash
shopt -s extglob
rm -rf src/uploads/php/files/!(.htaccess)
