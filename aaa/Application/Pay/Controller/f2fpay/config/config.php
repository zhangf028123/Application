<?php
$config = array (
		//签名方式,默认为RSA2(RSA2048)
		'sign_type' => "RSA2",

		//支付宝公钥
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvQwU46JWC+SJ0iXZJHqUMGQj2HBTAlEOJSMgNNoy9eMjARwvrVn+gG8wMval8GtOYRb0uJ+owImRzmBjp+8d6hQzJHgtyj827vUKe8OL853FRywrqEKk8Op1fww1rBmQIUjzeOTACh1pmKTapygwLm22WguSymBVyHojfsm6Aa+ZpVxlerOC9IZ3/enexFyPsrSuzUTkrX8bladg/S1O6d+7dlWSWvirKVcNy8OKXmjOFmGikiV+FiUfWRYJHAsoyabvzAGoz2/UeWxqtWYuvuh72g34rKD5um/V5g61BzmRNnv+uq6E/KWKQOvx6gp5ca9e9NLKPiC0Cy5Rt/BpZwIDAQAB
",

		//商户私钥
		'merchant_private_key' => "MIIEpAIBAAKCAQEAvQwU46JWC+SJ0iXZJHqUMGQj2HBTAlEOJSMgNNoy9eMjARwvrVn+gG8wMval8GtOYRb0uJ+owImRzmBjp+8d6hQzJHgtyj827vUKe8OL853FRywrqEKk8Op1fww1rBmQIUjzeOTACh1pmKTapygwLm22WguSymBVyHojfsm6Aa+ZpVxlerOC9IZ3/enexFyPsrSuzUTkrX8bladg/S1O6d+7dlWSWvirKVcNy8OKXmjOFmGikiV+FiUfWRYJHAsoyabvzAGoz2/UeWxqtWYuvuh72g34rKD5um/V5g61BzmRNnv+uq6E/KWKQOvx6gp5ca9e9NLKPiC0Cy5Rt/BpZwIDAQABAoIBAHCx7xH0CA924aHg5h0U5ZojWCsCarmK0D/bPKvFF3P1Pzy+LL3OVCUEI1t+JVW44jSGlsQoiVSdrcm1NDM8HD0aZZJsAf/6xyiT8vfsjlVfy+atsijP8bucSQa/pI8/fegZsOp0kvZ0qipQu/fBwVcsF/R9ybfSzdCA3wAKCVRO7ckmgBsQ7XNNr+k1Owmzn08X6ZHVcpruPvM7pd+0FCCsbTpw1OJ9QuED+WwngquDFY3TOCpSGg8cIh8r1hWog8g/bW6gyGAcHLYUYxhFQPlEx0XVgHhBsihInb1d03LEFabWWuMNBcNB2Yf4VwiLDvTdJGh0K/3FP+blNq+MXSECgYEA971u1bMZkXkydcmbHa1Xb4k9/CMxJ/kB0iVChrUUaPZEjSZUy6jXq885UR8dSSwkn0ZAxp9M9ICE4BGrlb1OmRPCDJPuAmMzpdaDHzRsOJ18q6J4F6/afXUSJb/z+74hT7bWAGhY1Mi7EcV8prPFsWko1qB3MoS/DRZq9HMc6RMCgYEAw1muOnQdC75NWzJpuTUeYtm1ptT22ZzrnyNsGxQpOodTe26NHNrYpZ4qq0u+CRXryxn8LWbCzmfmbm/Eeem3BX8XubAUwel3dyWkpDJQ13/6V/izbe/kRWzY6uZ3tsqoR+1kgf65IFtJc/z7ZYWRjkqnjLjzYD/i/bZtQHVSfN0CgYEA7hymwMMJmibQ9yE088s9tLhGWWdBwde1hlPFo0+8ND4vGTN0YOMBl+LuhifPsBq7gFK3w7As+Pvluq+BKcTwHHU/F3O/WZAbfhO1p3JtaeUEhLr9jla5O8ggDyR1zsqpncJv4ahpaOsd0jDsZBV5t9EJLXDB4E5yipO3bQiPCv0CgYBeibQLjc3QtRPyon8PpmlCJWIHjuC3h31v0lCq+iLJtFvuTB32bOKTo+u6YjlhZD5sV/L2ddio0xdtMqG+7iAohM0Si+g/v6CVBJ6c58y/vauFj1ImTgYGoxqG82nUCFdQF86gKomk4wi1HST8iJtcZTyYmZkOZ1yOtA5DN4Pn8QKBgQCYI3bZDhHhEM9evRcmG10jrNmUI6NnIlDzt/ev5WeTMosLlr7kJyACAW7BVlZRSGsExGJZXFISwApWFsC/INKn0g17XJiyxWq0e1XotdYsw6olPxqs7UOCcpc32O9pp3PaXGrM5jagPEsL0BxtAY5M5rhjHDgYpV4ymmvrjtb1rQ==",

		//编码格式
		'charset' => "UTF-8",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

		//应用ID
		'app_id' => "2015061100120633",

		//异步通知地址,只有扫码支付预下单可用
		'notify_url' => "http://www.baidu.com",

		//最大查询重试次数
		'MaxQueryRetry' => "10",

		//查询间隔
		'QueryDuration' => "3"
);