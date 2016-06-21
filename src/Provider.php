<?php

namespace SocialiteProviders\YouTube;

use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    const IDENTIFIER = 'YOUTUBE';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        "https://www.googleapis.com/auth/userinfo.email",
        "https://www.googleapis.com/auth/userinfo.profile",
        "https://www.googleapis.com/auth/youtube.readonly",
        "https://www.googleapis.com/auth/youtubepartner-channel-audit",
        "https://www.googleapis.com/auth/yt-analytics.readonly",
        "https://www.googleapis.com/auth/yt-analytics-monetary.readonly"
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://accounts.google.com/o/oauth2/auth', $state
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.google.com/o/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://www.googleapis.com/youtube/v3/channels?part=snippet,auditDetails,statistics&mine=true', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);
        $me = $this->getHttpClient()->get(
            'https://www.googleapis.com/plus/v1/people/me', [
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true)['items'][0];
        $data['me'] = json_decode($me->getBody()->getContents(), true);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['id'],
            'google_id' => $user['me']['id'],
            'nickname' => $user['snippet']['title'],
            'name' => null, 'email' => null,
            'avatar' => $user['snippet']['thumbnails']['high']['url']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}