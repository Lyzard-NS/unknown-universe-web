build:
	@docker-compose -p docms build
run:
	@docker-compose -p docms up -d xamppy
stop:
	@docker-compose -p docms down
restart:
	$(MAKE) stop
	$(MAKE) run
clean-data:
	@docker-compose -p docms down -v
clean-images:
	@docker rmi `docker images -q -f "dangling=true"`
full-reset:
	$(MAKE) clean-data
	$(MAKE) build
	$(MAKE) run
reload:
	@docker exec docms_xamppy_1 /opt/lampp/lampp restart
bash:
	@docker exec -ti docms_xamppy_1 bash