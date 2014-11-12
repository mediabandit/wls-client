<?php

namespace Wls;

/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author James McFadden <james@mediabandit.co.uk>
 */
class Client
{
    const PARAM_PUBLIC_KEY = 'dkey',
          PARAM_TIMESTAMP  = 'timestamp',
          PARAM_HASH       = 'hash',
          DEFAULT_URI      = 'http://api.whitelabelshopping.net';

    /**
     * The base URI to query
     *
     * @var string
     */
    protected static $baseUri;

    /**
     * List of required options
     *
     * @var array
     */
    protected $requiredOptions = array(
        'publicKey',
        'privateKey'
    );

    /**
     * Client options
     * 
     * @var array
     */
    protected $options = array();

    /**
     * cURL options
     * 
     * @var array
     */
    protected $curlOptions = array();

    /**
     * Set the API base URL
     * 
     * @param string $baseUrl
     */
    public static function setBaseUri($baseUri)
    {
        self::$baseUri = $baseUri;
    }

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $this->validateOptions($options);
        self::setBaseUri(self::DEFAULT_URI);
    }

    /**
     * Set the cURL options to use when making a request
     * 
     * @param array $options
     */
    public function setCurlOptions(array $options)
    {
        $this->curlOptions = $options;
    }

    /**
     * Set an individual cURL option
     * 
     * @param string $option
     * @param mixed $value
     */
    public function setCurlOption($option, $value)
    {
        $this->curlOptions[$option] = $value;
    }

    /**
     * Perform a search
     * 
     * @param  array  $parameters
     * @return array
     */
    public function search(array $parameters)
    {
        $url = $this->signUrl(self::$baseUri . '/search' . '?' . http_build_query($parameters));
        return json_decode($this->makeRequest($url), true);
    }

    /**
     * Sign a URL
     * 
     * @param  string $url
     * @return string
     */
    public function signUrl($url)
    {
        $parts = parse_url($url);

        // Normalise the query string by sorting by parameter name
        if (isset($parts['query'])) {
            parse_str($parts['query'], $params);
            ksort($params);

            $parts['query'] = http_build_query($params);
            
            $url = $parts['scheme'] . '://' . $parts['host'] .
                (isset($parts['path']) ? $parts['path'] : '') . '?' . $parts['query'] . '&';
        } else {
            $url .= '?';
        }

        // Add the public key and current timestamp
        $url .= self::PARAM_PUBLIC_KEY . '=' . $this->options['publicKey'] .
            '&' . self::PARAM_TIMESTAMP . '=' . $this->getTimestamp();

        // Create a hash token using the private key
        $hashToken = $url . $this->options['privateKey'];

        // Hash, pack and encode the token
        $hash = $this->createSignedHash($hashToken);

        // Append the generated hash to the URL
        return $url . '&' . self::PARAM_HASH . '=' . $hash;
    }

    /**
     * Make a request to a uri
     * 
     * @param  string $uri
     * @return string
     */
    protected function makeRequest($uri)
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->curlOptions);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * Validate options against required options
     * 
     * @param  array  $options
     * @return array
     * @throws Wls\Exception
     */
    protected function validateOptions(array $options)
    {
        foreach ($this->requiredOptions as $option) {
            if (!array_key_exists($option, $options)) {
                throw new Exception('Missing required option "' . $option . '"');
            }
        }
        return $options;
    }

    /**
     * Pack a URL into a hashed key
     * 
     * @param  string $url
     * @return string
     */
    protected function createSignedHash($url)
    {
        return str_replace(
            array('+', '/', '='),
            array('.', '_', '-'),
            base64_encode(pack('H*', md5($url)))
        );
    }

    /**
     * Return the current timestamp
     * 
     * @return int
     */
    protected function getTimestamp()
    {
        return time();
    }
}
