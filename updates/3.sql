CREATE TABLE social_user_providers (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    provider_id varchar(255) DEFAULT NULL,
    provider_token varchar(255) DEFAULT NULL,
    is_enabled tinyint(4) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY provider_id (provider_id),
    KEY is_enabled (is_enabled)
);