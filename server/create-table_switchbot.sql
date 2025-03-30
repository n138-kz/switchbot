CREATE TABLE IF NOT EXISTS switchbot
(
	"timestamp" double precision NOT NULL UNIQUE PRIMARY KEY DEFAULT EXTRACT(epoch FROM CURRENT_TIMESTAMP),
	remote_addr text NOT NULL,
	remote_port text NOT NULL,
	useragent text NOT NULL,
	evented_on text NOT NULL,
	scene_id text,
	result_status boolean
);
