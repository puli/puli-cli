# Deploy

```
ssh-keygen -t rsa -b 4096 -C "<your email address>"
```

* store deploy key in github

cp $HOME/.ssh/<repo>_rsa .travis/build-key.pem
