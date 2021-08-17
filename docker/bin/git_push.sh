#!/usr/bin/env bash

setup_git() {
  git config --global user.email "noreply@github.com"
  git config --global user.name "Github Action"
}

commit_readme() {
    git add $1
    git commit --message "Updated commands docu in README.md. (#$GITHUB_RUN_NUMBER) [ci skip]"
}

upload_files() {
  git remote add oxprojects https://${GITHUB_TOKEN}@github.com/OXIDprojects/oxrun.git > /dev/null 2>&1
  git push --quiet --set-upstream oxprojects
}

BASE_DIR=${GITHUB_WORKSPACE};
README="$BASE_DIR/README.md";

setup_git
commit_readme "$README"
upload_files
