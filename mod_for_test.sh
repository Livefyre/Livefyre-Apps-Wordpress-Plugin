#! /bin/bash

if [[ -z $1 || -z $2 ]]; then
	echo "usage: $0 PLUGINFILE HOST"
    echo "   eg: $0 livefyre-3_52.zip usea1p70"
	exit 1
fi

PLUGINFILE=$1
if [ ! -f $PLUGINFILE ]; then
    echo "Invalid plugin file: $PLUGINFILE"
    echo "Maybe you need to run ./build.sh first?"
    exit 1
fi
HOST=$2

OUTPUTFILE="${HOST}-${PLUGINFILE}"
if [ -f $OUTPUTFILE ]; then
    echo "please remove the old plugin first..."
    echo "(eg: rm $OUTPUTFILE)"
    exit 1
fi

TMPDIR=$(mktemp -d -t lfplugin)
if [ "$?" -ne 0 ]; then
    echo "Couldn't create temp dir."
    exit 1
fi
echo "modding $PLUGINFILE for $HOST in temporary $TMPDIR"

cp $PLUGINFILE $TMPDIR
COPIED="$TMPDIR/$(basename $PLUGINFILE)"
echo $COPIED
# du -h $COPIED

unzip $COPIED -d $TMPDIR
PLUGINDIR="$TMPDIR/livefyre-comments"
echo "Unpacked into $PLUGINDIR"
# find $PLUGINDIR

perl -p -i -e "s/'LF_DEFAULT_TLD', 'livefyre.com'/'LF_DEFAULT_TLD', '$HOST.livefyre.com'/" "$PLUGINDIR/livefyre_core.php"
# cat "$PLUGINDIR/livefyre_core.php"

OUTPUTPATH="$PWD/$OUTPUTFILE"
(cd $TMPDIR && zip -r $OUTPUTFILE livefyre-comments && mv $OUTPUTFILE $OUTPUTPATH)
# # tar -czf livefyre-3_51-$1.tar.gz livefyre-comments/

rm -rf $TMPDIR
