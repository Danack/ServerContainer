#!/bin/bash


set -x #echo on

users=()
users+=("blog")
users+=("intahwebz")
users+=("servercontainer")
users+=("tierjigdocs")
users+=("tierjigskeleton")

for user in "${users[@]}"
do
   :
   . ./build/createUser.sh $user
done