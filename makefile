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
	(cd temp-vip-svn; svn co https://vip-review-svn.wordpress.com/plugins/livefyre/ .; cp -r ../livefyre-apps/* .; svn diff > wp-vip-diff.txt; mv wp-vip-diff.txt ../;)
	if [ ! -s wp-vip-diff.txt ] then
		echo "There is nothing different in your local."
		exit 1
	fi

deploy_org:
	# .org push
	echo "Starting .org deploy."
	mkdir temp-org-svn
	( 
		cd temp-vip-svn; \
		svn co http://plugins.svn.wordpress.org/livefyre-apps .; \
		cp -r ../livefyre-apps/* .; \
	)

clean:
	rm -rf temp-org-svn
	rm -rf temp-vip-svn
