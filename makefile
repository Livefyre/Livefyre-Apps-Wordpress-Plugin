ENV = env

build: zip

env:
	echo "TODO: local build"

zip:
	zip -r livefyre-apps.zip livefyre-apps/

deploy_all: deploy_vip
	echo "Deploying to VIP and .org"

deploy_vip:
	# VIP push
	echo "Starting VIP deploy."
	mkdir temp-vip-svn
	( cd temp-vip-svn; svn co https://vip-review-svn.wordpress.com/plugins/livefyre/ .; cp -r ../livefyre-apps/* .; svn diff > difff.txt; mv difff.txt ../; )
		# bash regex on readme
		# bash regex on livefyre-apps.php
		# cp -r ../livefyre-apps/* .
		# svn diff > livefyre-apps-vip-diff.txt
		# email somewhere?
	if [ -s wp-vip-diff.txt ] then
		#email it to people
	else
		echo "There is nothing different in your local."
	fi

deploy_org:
	# .org push
	echo "Starting .org deploy."

	mkdir temp-org-svn
	pushd $(PWD)/temp-org-svn
		svn co http://plugins.svn.wordpress.org/livefyre-apps/
	# 	svn diff
		# bash regex on readme
		# bash regex on livefyre-apps.php
		# push to org?
	popd

clean:
	rm -rf temp-org-svn
	rm -rf temp-vip-svn
