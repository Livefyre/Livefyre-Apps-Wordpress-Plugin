#! /bin/bash

echo "$@" | grep -o -- '--force' && ARG_FORCE=true || ARG_FORCE=false

# Check output file
OUTPUTFILE="livefyre-`sed 's/\./_/' version`.zip"
if $ARG_FORCE; then
    echo "Forcing overwrite of $OUTPUTFILE."
elif [ -f $OUTPUTFILE ]; then
    echo "Cannot overwrite, please remove existing plugin:"
    echo "(eg: rm $OUTPUTFILE)"
    exit 1
fi

# Grab the livefyre-api
git submodule init
git submodule update

# Build the zip file with a prescribed name
zip -r $OUTPUTFILE livefyre-comments/ -x "livefyre-comments/**/.*" -x "livefyre-comments/.*"

# Indicate success
echo ""
echo "* Made a new zip file for you: $OUTPUTFILE"
