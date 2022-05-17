<?php
namespace App\Services\Notifications\Discord;

use Closure;

class DiscordMessage
{
    /** @var string */
    protected string $webHookUrl = '';

    /** @var string */
    protected string $content = '';

    /** @var string */
    protected string $username = '';

    /** @var string */
    protected string $avatarUrl = '';

    /** @var bool */
    protected bool $tts = false;

    /** @var array */
    protected array $embeds = [];

    /**
     * @param string $content
     * @return $this
     */
    public function content(string $content) : self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function username(string $username) : self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param string $avatarUrl
     * @return $this
     */
    public function avatarUrl(string $avatarUrl) : self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    /**
     * @param bool $tts
     * @return $this
     */
    public function tts(bool $tts) : self
    {
        $this->tts = $tts;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function to(string $url) : self
    {
        $this->webHookUrl = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getWebHookUrl() : string
    {
        return $this->webHookUrl;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        $message = [
            'content' => $this->content,
            'username' => $this->username,
            'avatar_url' => $this->avatarUrl,
            'tts' => $this->tts
        ];

        if (count($this->embeds) > 0) {
            $message['embeds'] = [];
            /** @var DiscordEmbed $embed */
            foreach ($this->embeds as $embed) {
                $message['embeds'][] = $embed->toArray();
            }
        }

        return $message;
    }

    /**
     * @param Closure $callback
     * @return $this
     */
    public function embed(Closure $callback) : self
    {
        $this->embeds[] = $embed = new DiscordEmbed();

        $callback($embed);

        return $this;
    }
}
