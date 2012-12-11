#!/bin/bash

version=`sed 's/\./_/' version`
archive=livefyre-${version}.zip

zip -r $archive livefyre-comments/ -x "livefyre-comments/**/.*" -x "livefyre-comments/.*"

echo "
*Made a new zip file for you: $archive"