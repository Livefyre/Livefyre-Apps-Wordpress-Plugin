#! /bin/bash
# A modification of Dean Clatworthy's deploy script as found here: https://github.com/deanc/wordpress-plugin-git-svn
# The difference is that this script lives in the plugin's git repo & doesn't require an existing SVN repo.

# main config
PLUGINSLUG="livefyre-apps"
CURRENTDIR=`pwd`
MAINFILE="livefyre-apps.php" # this should be the name of your main php file in the wordpress plugin

# git config
GITPATH="$CURRENTDIR/" # this file should be in the base of your git repository

# svn config
SVNPATH="/tmp/$PLUGINSLUG" # path to a temp SVN repo. No trailing slash required and don't add trunk.
SVNURL="http://plugins.svn.wordpress.org/livefyre-apps/" # Remote SVN repo on wordpress.org, with no trailing slash
SVNUSER="Livefyre" # your svn username
SVNPASSWORD=$LF_WP_ORG_PASSWORD
SVNDEST="branches/1.2"


# Let's begin...
echo ".........................................."
echo 
echo "Preparing to deploy wordpress plugin"
echo 
echo ".........................................."
echo 

# Check if subversion is installed before getting all worked up
if ! which svn >/dev/null; then
	echo "You'll need to install subversion before proceeding. Exiting....";
	exit 1;
fi

# Check version in readme.txt is the same as plugin file after translating both to unix line breaks to work around grep's failure to identify mac line breaks
NEWVERSION1=`grep "^Stable tag:" $GITPATH/$PLUGINSLUG/readme.txt | awk -F' ' '{print $NF}'`
echo "readme.txt version: $NEWVERSION1"
NEWVERSION2=`grep "^Version:" $GITPATH/$PLUGINSLUG/$MAINFILE | awk -F' ' '{print $NF}'`
echo "$MAINFILE version: $NEWVERSION2"

if [ "$NEWVERSION1" != "$NEWVERSION2" ]; then echo "Version in readme.txt & $MAINFILE don't match. Exiting...."; exit 1; fi

echo "Versions match in readme.txt and $MAINFILE. Let's proceed..."

if git show-ref --tags --quiet --verify -- "refs/tags/$NEWVERSION1"
	then 
		echo "Version $NEWVERSION1 already exists as git tag. Exiting...."; 
		exit 1; 
	else
		echo "Git version does not exist. Let's proceed..."
fi

# For sanity reasons
echo ".........................................."
echo 
echo "Parameters:"
echo "Plugin slug: $PLUGINSLUG"
echo "Current directory: $CURRENTDIR"
echo "Main plugin file: $MAINFILE"
echo "Path to git repo: $GITPATH"
echo "Path to svn temp dir: $SVNPATH"
echo "svn url: $SVNURL"
echo "svn username: $SVNUSER"
echo
echo ".........................................."
echo 

#cd $GITPATH
#echo -e "Enter a commit message for this new version: \c"
#read COMMITMSG
#git commit -am "$COMMITMSG"

#echo "Tagging new version in git"
#git tag -a "$NEWVERSION1" -m "Tagging version $NEWVERSION1"

#echo "Pushing latest commit to origin, with tags"
#git push origin master
#git push origin master --tags

echo 
echo "Creating local copy of SVN repo ..."
svn co $SVNURL $SVNPATH

echo "Clearing svn repo so we can overwrite it"
svn rm -f $SVNPATH/$SVNDEST/*

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/$SVNDEST/

echo "Ignoring github specific files and deployment script"
svn propset svn:ignore "deploy.sh
install.sh
livefyre-wpvip-page.txt
makefile
README.md
.git
.gitignore" "$SVNPATH/$SVNDEST/"

echo "Changing directory to SVN and committing to trunk"
cd $SVNPATH/$SVNDEST/

# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add
# svn commit --username=$SVNUSER --password$SVNPASSWORD -m "$COMMITMSG"

# echo "Creating new SVN tag & committing it"
# cd $SVNPATH
# svn copy trunk/ tags/$NEWVERSION1/
# cd $SVNPATH/tags/$NEWVERSION1
# svn commit --username=$SVNUSER -m "Tagging version $NEWVERSION1"

# echo "Removing temporary directory $SVNPATH"
# rm -fr $SVNPATH/

echo "*** FIN ***"
