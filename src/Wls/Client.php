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
          PARAM_HASH       = 'hash';

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
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $this->validateOptions($options);
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
