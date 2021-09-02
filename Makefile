
watch:
	docker run --rm \
	  --name githup-jekyll \
      --volume="$(PWD):/srv/jekyll" \
      --publish 4000:4000 \
      -it jekyll/jekyll \
      jekyll serve
