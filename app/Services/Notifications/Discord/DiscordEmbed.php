<?php
namespace App\Services\Notifications\Discord;

class DiscordEmbed
{
    /** @var string */
    protected string $title = '';

    /** @var string */
    protected string $type = 'rich';

    /** @var string */
    protected string $description = '';

    /** @var string */
    protected string $url = '';

    /** @var int */
    protected int $color = 0;

    /** @var array */
    protected array $fields = [];

    /**
     * @param string $title
     * @return $this
     */
    public function title(string $title) : self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     * @throws \Exception
     */
    public function type(string $type) : self
    {
        $type = strtolower($type);
        if (!in_array($type, ['rich', 'image', 'video', 'gifv', 'article', 'link'])) {
            throw new \Exception('Invalid Discord Embed Type: ' . $type);
        }
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function description(string $description) : self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function url(string $url) : self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param int $color
     * @return $this
     */
    public function color(int $color) : self
    {
        $this->color = $color;
        return $this;
    }

    /**
     * @return $this
     */
    public function success() : self
    {
        $this->color = 5426201;
        return $this;
    }

    /**
     * @return $this
     */
    public function error() : self
    {
        $this->color = 16711680;
        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     * @throws \Exception
     */
    public function fields(array $fields) : self
    {
        if (count($fields) > 0) {
            // Do some validation.
            foreach ($fields as $field) {
                if (!isset($field['name'], $field['value'])) {
                    throw new \Exception('Invalid Discord Embed Fields - Either key name|value is missing');
                }
            }
        }
        $this->fields = $fields;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        return [
            'title' => $this->title,
            'type' => $this->type,
            'description' => $this->description,
            'url' => $this->url,
            'color' => $this->color,
            'fields' => $this->fields
        ];
    }
}
