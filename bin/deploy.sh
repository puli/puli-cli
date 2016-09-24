#!/bin/bash
# Unpack secrets; -C ensures they unpack *in* the .travis directory
tar xvf .travis/secrets.tar -C .travis

# Setup SSH agent:
eval "$(ssh-agent -s)" #start the ssh agent
chmod 600 .travis/build-key.pem
ssh-add .travis/build-key.pem

# Get box and build PHAR
wget https://box-project.github.io/box2/manifest.json
BOX_URL=$(php bin/parse-manifest.php manifest.json)
rm manifest.json
wget -O box.phar ${BOX_URL}
chmod 755 box.phar
./box.phar build -vv

# TODO if build with gh-pages

# Setup git defaults:
# TODO set username and email
# git config --global user.email "johannes.wachter@outlook.com"
# git config --global user.name "Johannes Wachter"

# Add SSH-based remote to GitHub repo:
# TODO remote
# git remote add deploy git@github.com:wachterjohannes/cli.git
# git fetch deploy

# Checkout gh-pages and add PHAR file and version:
# git checkout -b gh-pages deploy/gh-pages
# git pull deploy gh-pages
# mv build/* ./puli.phar
# sha1sum puli.phar > puli.phar.version
# git add puli.phar puli.phar.version

# Commit and push:
# git commit -m 'Rebuilt phar'
# git push deploy gh-pages:gh-pages
