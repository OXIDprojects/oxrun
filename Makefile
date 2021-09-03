
watch:
	docker run --rm \
	  --name githup-jekyll \
      --volume="$(PWD):/srv/jekyll" \
      --publish 4000:4000 \
      --publish 35729:35729 \
      -it jekyll/jekyll \
      jekyll serve \
      --source ./docs \
      --destination /tmp/_site \
      --livereload \

