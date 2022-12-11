<?php

namespace Sabo\Sabo;

use \PHPMailer\PHPMailer\PHPMailer;

use \Twig\Environment;

use \Exception;


/*
	to send a classic mail

	->set_template_data()
	->set_template_file()
	->set_subject()
	->set_alt_body()
	->add_addresses();

	->send_mail()
*/
class Mailer
{
	private PHPMailer $mailer;

	private bool $config_is_set;

	private array $config;
	private array $template_data;

	private string $template_file;

	private Environment $twig_environment;

	public function __construct(Environment $twig_environment)
	{
		$this->mailer = new PHPMailer(false);

		$this->mailer->isSMTP();
        $this->mailer->CharSet = "UTF-8";
        $this->mailer->Encoding = "base64";
        $this->mailer->SMTPSecure = "ssl";
        $this->mailer->SMTPDebug = 0;
        $this->mailer->Port = 465;
        $this->mailer->SMTPAuth = true;

        $this->twig_environment = $twig_environment;

        $this->config = [
        	"email" => "",
        	"host" => "",
        	"password" => ""
        ];

        switch(CONFIG_FILE_TYPE)
        {
        	case Router::JSON_ENV:
        		list("email" => $this->config["email"],"host" => $this->config["host"],"password" => $this->config["password"]) = $_ENV["mailer"];
        	; break;

        	case Router::CLASSIC_ENV:
        		list("MAILER_EMAIL" => $this->config["email"],"MAILER_HOST" => $this->config["host"],"MAILER_PASSWORD" => $this->config["password"]) = $_ENV;
        	; break;
        }

        $this->default_config();
	}

	public function default_config():void
	{
		$this->mailer->isHTML(true);
        $this->mailer->setFrom($this->config["email"],$_ENV["appname"]);
        $this->mailer->Username = $this->config["email"];
        $this->mailer->Subject = "";
        $this->mailer->AltBody = "";
        $this->template_data = [];

        list(
        	"host" => $this->mailer->Host,
        	"password" => $this->mailer->Password
        ) = $this->config;

        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
	}

	// for special config
	public function get_mailer():PHPMailer
	{
		return $this->mailer;
	}

	public function set_is_html(bool $is_html = true):self
	{
		$this->mailer->isHTML($is_html);

		return $this;
	}

	public function set_from(?string $address = NULL,?string $name = NULL):self
	{
		$this->mailer->setFrom($address != NULL ? $address : $this->config["email"],$name != NULL ? $name : $_ENV["appname"]);

		return $this;
	}

	public function set_username(string $username):self
	{
		$this->mailer->Username = $username;

		return $this;
	}

	public function set_host(string $host):self
	{
		$this->mailer->Host = $host;

		return $this;
	}

	public function set_password(string $password):self
	{
		$this->mailer->Password = $password;

		return $this;
	}

	public function set_subject(string $subject):self
	{
		$this->mailer->Subject = $subject;

		return $this;
	}

	public function set_alt_body(string $alt_body):self
	{
		$this->mailer->AltBody = $alt_body;

		return $this;
	}

	public function set_template_data(array $template_data):self
	{
		$this->template_data = $template_data;

		return $this;
	}

	public function set_template_file(string $template_file):self
	{
		$this->template_file = $template_file;

		return $this;
	}

	public function set_twig_environment(Environment $twig_environment):self
	{
		$this->twig_environment = $twig_environment;

		return $this;
	}

	public function add_addresses(string|array $addresses,bool $clear_addresses = false):self
	{
		if($clear_addresses)
			$this->mailer->clearAddresses();

		if(gettype($addresses) == "string")
			$addresses = [$addresses];

		foreach($addresses as $address)
			$this->mailer->addAddress($address);

		return $this;
	}

	public function add_attachments(array... $attachments_data):self
	{
		foreach($attachments_data as $attachment_data)
		{
			$params = [
				"path" => "",
				"name" => "Fichier joint {$_ENV["appname"]}",
				"encoding" => PHPMailer::ENCODING_BASE64,
				"type" => "",
				"disposition" => "attachment"
			];

			if(gettype($attachment_data) != "array")
				continue;

			foreach($attachment_data as $key => $data)
				$params[$key] = $data; 

			$this->mailer->addAttachment($params["path"],$params["name"],$params["encoding"],$params["type"],$params["disposition"]);
		}

		return $this;
	}

	public function clear_addresses():self
	{
		$this->mailer->clearAddresses();

		return $this;
	}

	public function clear_attachments():self
	{
		$this->mailer->clearAttachments();

		return $this;
	}

	public function send_mail():bool
	{
        try
        {
        	$this->mailer->Body = $this->twig_environment->render($this->template_file,$this->template_data);

            return $this->mailer->send();	
        }
        catch(Exception)
        {
        	return false;
        }
    }
}