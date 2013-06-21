#!/bin/bash

# Grab the arguments and decide which plguin to make
while getopts n:mecs option
do
    case "${option}"
    in
        n) BLOGNAME="${OPTARG}";;
        m) MULTISITE=1;;
        e) ENTERPRISE=1;;
        c) COMMUNITY=1;;
	    s) SITESYNC=1;;
        \?) exit;;
    esac
done

# Decides which system we are on and what sed to run
IS_BSD=$([ "$(uname -s)" == "Darwin" ] && echo 1)
function sed_i () {
    if [ ${IS_BSD} ]; then
        sed -i '' "$@"
    else
        sed -i "$@"
    fi
}

# Make sure everything is there and either enterprise OR community is selected. Not both.
if [[ $COMMUNITY && $ENTERPRISE ]]; then
    echo "You cannot have a Community and Enterprise Version at the same time"
    exit 1
fi

if [[ -z $COMMUNITY && -z $ENTERPRISE ]]; then
    echo "You need to specify at least Community or Enterprise"
    exit 1
fi

RUNPATH=$(pwd)

PATHROOT=$( cd $(dirname $0) ; pwd -P )

# Need to update all of the files to include the right version from the version file
# This is one of the whole reasons. For awesome version tracking
VERSION=`cat version`
for filename in $(find livefyre-comments/src/ -type f); do
    sed_i "s/.*Version:.*/Version: $VERSION/" "$filename"
done
sed_i "s/.*Version:.*/Version: $VERSION/" "$PATHROOT/livefyre-comments/livefyre.php"

# Update the stable tag
sed_i "s/.*Stable tag:.*/Stable tag: ${VERSION%%-*}/" "$PATHROOT/livefyre-comments/readme.txt"

# Updating the git README.md
cat "$PATHROOT/livefyre-comments/readme.txt" > "$PATHROOT/README.md"

# Outlying plugin version in core needs updating
sed_i "s/.*define( 'LF_PLUGIN_VERSION',.* );/define( 'LF_PLUGIN_VERSION', '$VERSION' );/" "$PATHROOT/livefyre-comments/src/Livefyre_WP_Core.php"

# Create a temp directory to store changed plugins
mkdir "$PATHROOT/temp_build"
cp -r "$PATHROOT/livefyre-comments" "$PATHROOT/temp_build/"
# Path to the temp working directory
TEMPPATH="$PATHROOT/temp_build"
SRCPATH="$TEMPPATH/livefyre-comments/src"

# sed things to make the plugin build for ceratin options.
# Options are:
# Community: Multisite turned off and force use of Livefyre.com domain.
# Enterprise: Allow the use of any domain as well as a more robust settings page to add code snippits.
# 	Multisite: Allow for multisite support
# 	Site Sync: Allow for site_sync support
# All files excluded need to be relative because ZIP doesn't care about your absolute paths. Stupid ZIP
if [[ $COMMUNITY ]]; then
    PLUGINNAME=livefyre-wordpress-c.zip
    echo $PLUGINNAME
	echo "Building Community Plugin"
	# Remove enterprise stuff from the plugin
	EXCLUDES="livefyre-comments/src/admin/enterprise-settings.php livefyre-comments/src/admin/enterprise-multisite.php"

elif [[ $ENTERPRISE ]]; then
    PLUGINNAME=livefyre-wordpress-e.zip
	echo "Building Enterprise Plugin"

    # Exclude Community things
    EXCLUDES="livefyre-comments/src/admin/settings-template.php livefyre-comments/src/admin/multisite-settings.php livefyre-comments/src/import/Livefyre_Import_Impl.php"

    # sed-ing the description and plugin name so that enterprise users don't upgrade their plugin
    sed_i 's/Plugin Name: Livefyre Realtime Comments/Plugin Name: Livefyre Enterprise Realtime Comments/' "$TEMPPATH/livefyre-comments/livefyre.php"
    sed_i 's/Description: Implements Livefyre realtime comments for WordPress/Description: Implements Enterprise Livefyre realtime comments for WordPress/' "$TEMPPATH/livefyre-comments/livefyre.php"
    
    # Update the stable tag
    sed_i "s/.*Stable tag:.*/Stable tag: ${VERSION%%-*}/" "$PATHROOT/livefyre-comments/readme.txt"

	# sed-ing the settings page to use the enterprise version
	sed_i 's/\/settings-template.php/\/enterprise-settings.php/g' "$SRCPATH/admin/Livefyre_Admin.php"
    # rm Livefyre_Admin.php.bak

	# Check if we need mulitsite
	if [[ $MULTISITE ]]; then
        PLUGINNAME="${PLUGINNAME%%.*}-m.zip"
    	echo "Adding multisite related plugin items!"

    	# Add in multisite level stuff
		sed_i 's/\/multisite-settings.php/\/enterprise-multisite.php/g' "$SRCPATH/admin/Livefyre_Admin.php"

    else
        echo "Removing multisite related plugin items!"

        # Remove Multisite stuff
        sed_i "/define( 'LF_MULTI_SETTINGS_PAGE', '\/multisite-settings.php' );/d" "$SRCPATH/admin/Livefyre_Admin.php"
        EXCLUDES="$EXCLUDES livefyre-comments/src/admin/enterprise-multisite.php"
	fi

    # Check if we need site sync
	if [[ $SITESYNC ]]; then
        PLUGINNAME="${PLUGINNAME%%.*}-s.zip"
		echo "Stubbing out Site Sync related items!"

        # Add in implemented Site Sync
        sed_i 's/Livefyre_Sync_Impl/Livefyre_Sync_Stub/g' "$SRCPATH/Livefyre_WP_Core.php"
        sed_i '/require_once( dirname( __FILE__ ) . "\/src\/sync\/sync_helpers.php" );/d' "$TEMPPATH/livefyre-comments/livefyre.php"
        EXCLUDES="$EXCLUDES livefyre-comments/src/sync/Livefyre_Sync_Impl.php"
	fi

    # Switch import to stub. Not needed for enterprise
    sed_i 's/Livefyre_Import_Impl/Livefyre_Import_Stub/g' "$SRCPATH/Livefyre_WP_Core.php"

fi

# Actually build the new files now and come back down to temp_build
pushd $TEMPPATH
zip -r $PLUGINNAME livefyre-comments/ -x "livefyre-comments/**/.*" -x "livefyre-comments/.*"

# Builds backwards. Since all the code is already included, we need to get rid of things that aren't needed
for EXCLUDE in $EXCLUDES; do
    echo "Removing: $EXCLUDE"
    zip -d $PLUGINNAME $EXCLUDE
done

# Get the plugin out of the directory before we destroy it
echo mv -f $PLUGINNAME $RUNPATH
mv -f $PLUGINNAME $RUNPATH

popd

# Delete temp files as each build is different
rm -r $TEMPPATH
