if [ -z "$1" ]
  then
    echo "please specify target-directory on server"
    exit 1
fi

rsync -avC --delete bundles/tmp/Backend/MoptAvalara/ root@mediao.pt:/var/www/html/avalara/shopware/$1/engine/Shopware/Plugins/Community/Backend/MoptAvalara
