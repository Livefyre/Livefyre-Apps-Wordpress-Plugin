vip_diff:
	mkdir temp/
	mkdir temp/zip
	mkdir temp/vip
	make zip
	mv livefyre-apps.zip temp/zip
	svn co https://vip-svn.wordpress.com/plugins/livefyre-apps/ temp/vip
	unzip temp/zip/livefyre-apps.zip -d temp/vip
	svn diff temp/vip/ > vip-diff.txt
	rm -rf temp

deploy:
	./deploy.sh

build: zip

zip:
	zip -r livefyre-apps.zip . -x ".*" "vip-diff.txt" "deploy.sh" "install.sh" "livefyre-wpvip-page.txt" "makefile" "README.md" "*.git*" "*.gitignore*" "*.svn*" "*.idea*" "*.DS_Store*"
