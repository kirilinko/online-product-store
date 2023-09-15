# !/bin/bash
# this checks out each of your branches 
# and if you have an exercisefiles
# directory on your desktop, it copies a certain folder from each
# branch info said exercise files folder. Super handy for making
# an exercise files folder post course record.

mkdir ~/Desktop/exfiles
for BRANCH in `git branch --list|sed 's/\*//g'`
do
git checkout $BRANCH
mkdir ~/Desktop/exfiles/$BRANCH
cp -R ./* ~/Desktop/exfiles/$BRANCH/
done
