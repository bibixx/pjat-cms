<?php

namespace YummyNoodles\Component\Todos\Site\Controller;

defined('_JEXEC') or die;
require_once __DIR__.'/../../vendor/autoload.php';

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;

class DisplayController extends BaseController {
    private function getRedirectURL() {
      $protocol = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ? 'http' : 'https';
      $host = $_SERVER['HTTP_HOST'];
      $baseUrl = "$protocol://$host";

      return "$baseUrl/index.php?option=com_todos";
    }

    private function getArticle($id) {
      $model = \JModelLegacy::getInstance('Article', 'ContentModel');
      return $article = $model->getItem($id);
    }

    private function getIngredients($article) {
      \JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');
      $fields = \FieldsHelper::getFields('com_content.article', $article, true);

      $trim = function($txt) {
        return trim($txt);
      };

      foreach ($fields as $key => $field)
      {
        if ($field->name === 'skladniki') {
          $value = $field->rawvalue;
          $elements = array_map($trim, explode("\n", $value));

          return $elements;
        }
      }
    }

    private function getClient() {
      $client = new \Google\Client();
      $client->setAuthConfigFile(__DIR__.'/../../client_secrets.json');
      $client->setRedirectUri($this->getRedirectURL());
      $client->addScope("https://www.googleapis.com/auth/tasks");
      $client->addScope("https://www.googleapis.com/auth/tasks.readonly");

      if (isset($_SESSION['access_token'])) {
        $client->setAccessToken($_SESSION['access_token']);
      }

      return $client;
    }

    private function handleRedirectToGoogle() {
      $client = $this->getClient();

      $auth_url = $client->createAuthUrl();
      $this->setRedirect(filter_var($auth_url, FILTER_SANITIZE_URL));
    }

    private function handleAuthorization() {
      $client = $this->getClient();

      $client->authenticate($_GET['code']);
      $_SESSION['access_token'] = $client->getAccessToken();
    }

    private function redirectToArticle($article) {
      $url = \JRoute::_(
        \ContentHelperRoute::getArticleRoute(
          $article->id,
          $article->catid,
          $article->language
        )
      );

      $this->setRedirect($url);
    }

    private function getTaskListId() {
      $client = $this->getClient();
      $httpClient = $client->authorize();

      $response = $httpClient->get('https://www.googleapis.com/tasks/v1/users/@me/lists');
      $body = json_decode($response->getBody());

      var_dump($body);

      return $body->items[0]->id;
    }

    private function addTodo($tasklistId, $title) {
      $client = $this->getClient();
      $httpClient = $client->authorize();

      $requestBody = ['title' => $title];
      $response = $httpClient->post("https://www.googleapis.com/tasks/v1/lists/$tasklistId/tasks", [
        'json' => $requestBody
      ]);
      $body = json_decode($response->getBody());

      return $body;
    }

    private function handleAddingTodos() {
      $client = $this->getClient();
      $httpClient = $client->authorize();

      $article = $this->getArticle($_SESSION['todos_article_id']);

      $ingredients = $this->getIngredients($article);
      $taskListId = $this->getTaskListId();
      foreach ($ingredients as $ingredient) {
        $this->addTodo($taskListId, $ingredient);
      }

      unset($_SESSION['todos_article_id']);
      $this->redirectToArticle($article);
    }

    public function display($cachable = false, $urlparams = array()) {
      if (isset($_GET['code'])) {
        $this->handleAuthorization();
        $this->handleAddingTodos();
        return;
      }

      if (isset($_GET['article_id'])) {
        $_SESSION['todos_article_id'] = $_GET['article_id'];

        $this->handleRedirectToGoogle();
        return;
      }

      $this->setRedirect('/');
    }
}
