<?php

namespace Middleware\Middleware;

use \Model\Model\ArticleModel;

use \Middleware\Exception\MiddlewareException;

use \Model\Exception\ModelException;

use \TypeError;

// an example of middleware with example of transactions and use of middlewareexception

class ArticleMiddleware extends AbstractMiddleware
{
	/*
		try to create an article

		@post article_title
		@post article_content
	*/
	public function create_article():void
	{
		try
		{
			if(empty($_POST["article_title"]) || empty($_POST["article_content"]) )
				$this->throw_exception(MiddlewareException::MISSED_DATA);

			$article = new ArticleModel();

			$article
				->set_attribute("article_title",$_POST["article_title"])
				->set_attribute("article_content",$_POST["article_content"]);

			// example of transation use (you can just use the create method in this type of situation)
			if(ArticleModel::begin_transation() )
			{
				$article->create();

				if(!ArticleModel::commit_transaction() )
					ArticleModel::rollback_transaction();
				else
					$this->throw_exception(MiddlewareException::MODEL_EXCEPTION,new ModelException("Failed to start transaction",false) );
			}
			else $this->throw_exception(MiddlewareException::TECHNICAL_ERROR);
		}
		catch(ModelException $e)
		{
			ArticleModel::rollback_transaction();
			$this->throw_exception(MiddlewareException::MODEL_EXCEPTION,$e);
		}
		catch(TypeError)
		{
			ArticleModel::rollback_transaction();
			$this->throw_exception(MiddlewareException::BAD_DATA);
		}
	}
}