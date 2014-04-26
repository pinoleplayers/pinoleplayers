CREATE TABLE IF NOT EXISTS
	ProductCategory (
		categoryId					INT(10) UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
		categoryName				VARCHAR(255)			NOT NULL,
		categoryDescription	MEDIUMTEXT				NOT NULL,
		categoryType				VARCHAR(20)				NOT NULL,
		categoryLOGO				VARCHAR(100)			NULL,
		categoryCompany			VARCHAR(10)				NOT NULL	DEFAULT 'PCP'
);

CREATE TABLE IF NOT EXISTS
	Product (
		productId						INT(10) UNSIGNED		NOT NULL AUTO_INCREMENT PRIMARY KEY,
		categoryId					INT(10) UNSIGNED		NOT NULL,
		productDateTime			DATETIME						NOT NULL,
		productName					VARCHAR(255)				NULL,
		productSoldOut			TINYINT(1) UNSIGNED	NOT NULL	DEFAULT 0,
		productVisible			TINYINT(1) UNSIGNED	NOT NULL	DEFAULT 1,
		productPreSold			TINYINT(1) UNSIGNED	NOT NULL	DEFAULT 0,
		productTwofer				TINYINT(1) UNSIGNED	NOT NULL	DEFAULT 0,
		productIsAcomment		TINYINT(1) UNSIGNED	NOT NULL	DEFAULT 0
);

CREATE TABLE IF NOT EXISTS
	ProductPrice (
		priceId			INT(10) UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
		productId		INT(10) UNSIGNED	NOT NULL,
		priceClass	VARCHAR(100)			NOT NULL,
		classPrice	DECIMAL(6,2)			NOT NULL
);

CREATE TABLE IF NOT EXISTS
	Customer (
		customerId				INT(10) UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
		customerFirstName	VARCHAR(50)				NOT NULL,
		customerLastName	VARCHAR(50)				NOT NULL,
		customerEmail			VARCHAR(255)			NOT NULL,
		customerDayPhone	VARCHAR(20)				NULL,
		customerNitePhone	VARCHAR(20)				NULL,
		customerAddress		VARCHAR(100)			NULL,
		customerCity			VARCHAR(100)			NULL,
		customerState			VARCHAR(50)				NULL,
		customerZipcode		VARCHAR(20)				NULL,
		customerCountry		VARCHAR(100)			NULL	DEFAULT 'USA'
);


CREATE TABLE IF NOT EXISTS
	CustomerOrder (
		orderId					INT(10) UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
		customerId			INT(10) UNSIGNED	NOT NULL,
		orderCreatedOn	DATETIME					NOT NULL,
		invoice_number	CHAR(10)					NOT NULL,
		orderStatusId		INT(10) UNSIGNED	NOT NULL 
);

CREATE TABLE IF NOT EXISTS
	OrderItem (
		itemId						INT(10) UNSIGNED		NOT NULL AUTO_INCREMENT PRIMARY KEY,
		orderId						INT(10) UNSIGNED		NOT NULL,
		productPriceId		INT(10) UNSIGNED		NOT NULL,
		orderItemQuantity	TINYINT(2) UNSIGNED	NOT NULL,
		orderItemPrice		DECIMAL(6,2)				NOT NULL
);

CREATE TABLE IF NOT EXISTS
	OrderStatus (
		orderStatusiId	INT(10) UNSIGNED	NOT NULL AUTO_INCREMENT PRIMARY KEY,
		orderStatusName	VARCHAR(100)			NOT NULL,
		orderStatusCode	CHAR(1)						NOT NULL
);
