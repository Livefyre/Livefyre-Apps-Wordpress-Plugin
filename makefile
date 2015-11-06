deploy:
	./deploy.sh

build: zip

zip:
	zip -r livefyre-apps.zip . -x "deploy.sh" "install.sh" "livefyre-wpvip-page.txt" "makefile" "README.md" "*.git*" "*.gitignore*" "*.svn*" "*.idea*" "*.DS_Store*"
