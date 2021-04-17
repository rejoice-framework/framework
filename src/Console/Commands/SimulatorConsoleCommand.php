<?php

namespace Rejoice\Console\Commands;

use Prinx\Str;
use Prinx\Utils\URL;
use Rejoice\Console\Option;
use Rejoice\Simulator\Libs\Simulator;
use function Prinx\Dotenv\env;

class SimulatorConsoleCommand extends FrameworkCommand
{
    const APP_REQUEST_ASK_USER_RESPONSE = '2';

    const APP_REQUEST_CANCELLED = '30';

    const APP_REQUEST_END = '17';

    const APP_REQUEST_FAILED = '3';

    const APP_REQUEST_INIT = '1';

    const APP_REQUEST_USER_SENT_RESPONSE = '18';

    protected $simulatorMetadata = [
        'info' => 'question',
        'warning' => 'warning',
        'error' => 'error',
    ];

    public function configure()
    {
        $this->setName('simulator:console')
            ->setDescription('Run the USSD simulator in console')
            ->setHelp('This command allow you to test your USSD application from the console')
            ->addOption(
                'url',
                'u',
                Option::REQUIRED,
                'The endpoint to send the request to.',
                env('USSD_URL', '')
            )
            ->addOption(
                'tel',
                't',
                Option::REQUIRED,
                'The phone number to send the request with.',
                env('USSD_PHONE', '')
            )
            ->addOption(
                'ussd_code',
                'c',
                Option::REQUIRED,
                'The ussd code to use for the request.',
                env('USSD_CODE', '*380*57#')
            )
            ->addOption(
                'network',
                null,
                Option::REQUIRED,
                'The phone number network',
                env('USSD_NETWORK_MNC', '00')
            )
            ->addOption(
                'channel',
                null,
                Option::VALUE_OPTIONAL,
                'Makes the request behave like it was from the particular channel (from a normal phone, or from whatsapp or from the console). The allowed channels are: USSD, WHATSAPP, CONSOLE',
                env('USSD_CONSOLE_BEHAVIOR', 'USSD')
            );
    }

    public function fire()
    {
        $this->init();

        $this->endpoint = $this->getOption('url');

        if (!$this->endpoint || !URL::isUrl($this->endpoint)) {
            $this->writeWithColor('Invalid endpoint "'.$this->endpoint.'"', 'red');

            return SmileCommand::FAILURE;
        }

        if (!($tel = $this->getOption('tel'))) {
            $this->writeWithColor('Invalid phone number "'.$tel.'"', 'red');

            return SmileCommand::FAILURE;
        }

        return $this->simulate();
    }

    public function init()
    {
        $this->messageParameter = $this->config('app.request_param_menu_string');
        $this->sessionIdParameter = $this->config('app.request_param_session_id');
        $this->requestTypeParameter = $this->config('app.request_param_request_type');
        $this->userResponseParameter = $this->config('app.request_param_user_response');
        $this->userNumberParameter = $this->config('app.request_param_user_phone_number');
        $this->userNetworkParameter = $this->config('app.request_param_user_network');
    }

    public function simulate()
    {
        $simulator = new Simulator();
        $simulator->setEndpoint($this->endpoint);

        $this->dial();

        while (!$this->isLastMenu($this->payload)) {
            $simulator->setPayload($this->payload);
            $response = $simulator->callUssd();

            $responseData = json_decode($response->get('data'), true);

            if ($response->isSuccess() && is_array($responseData)) {
                $this->drawSeparationLine(['middle' => ' MENU ']);

                // $this->showMetadataIfExists($responseData);

                if ($this->ussdWantsUserResponse($responseData)) {
                    $this->getUserResponseAndSend($responseData);
                } else {
                    $this->handleUssdEnd($responseData);
                }
            } elseif (!Str::endsWith('/', $this->endpoint)) {
                $simulator->setEndpoint($this->endpoint .= '/');
                continue;
            } else {
                $this->showErrors($response);

                return SmileCommand::FAILURE;
            }
        }

        return SmileCommand::SUCCESS;
    }

    public function dial()
    {
        $this->payload = [
            $this->requestTypeParameter => self::APP_REQUEST_INIT,
            $this->userResponseParameter => $this->getOption('ussd_code'),
            $this->userNumberParameter => $this->getOption('tel'),
            $this->userNetworkParameter => $this->getOption('network'),
            $this->sessionIdParameter => time(),
            'channel' => $this->getOption('channel'),
        ];
    }

    public function end()
    {
        $this->payload[$this->requestTypeParameter] = self::APP_REQUEST_END;
    }

    public function generateDisplayTable($data)
    {
        $menuScreen = $this->createTable();
        $menuScreen->setHeaders(['MENU SCREEN'])
            ->addRow([$data['message']])
            ->setColumnMaxWidth(0, 50)
            ->setStyle('box')
            ->show();
    }

    public function getUserResponseAndSend($data)
    {
        $this->generateDisplayTable($data);
        $this->showMetadataIfExists($data);

        $userResponse = $this->ask("\n\nResponse: ");
        $this->payload[$this->userResponseParameter] = $userResponse;
        $this->payload[$this->requestTypeParameter] = self::APP_REQUEST_USER_SENT_RESPONSE;
    }

    public function handleUssdEnd($data)
    {
        $this->generateDisplayTable($data);
        $this->drawSeparationLine();

        if ($this->isLastMenu($data) && $this->wasSuccessfull($data)) {
            $this->info('USSD ENDED SUCCESSFULLY');
        }

        $this->showMetadataIfExists($data);

        if ($this->userWantsToDialAgain()) {
            $this->dial();

            return;
        }

        $this->end();
    }

    /**
     * Converts HTML text to string.
     *
     * @param string|string[] $messages A single string or an array of string
     *
     * @return string|string[]
     */
    public function htmlToText($messages)
    {
        if (is_string($messages)) {
            $stringPassed = true;
            $messagesPassed = [$messages];
        } else {
            $stringPassed = false;
            $messagesPassed = $messages;
        }

        $converted = [];

        foreach ($messagesPassed as $key => $value) {
            if (!is_string($value)) {
                return $messages;
            }

            $replacement = preg_replace('/<br[ ]*(\/?)>/', "\n", $value);
            $pat = '/<b>(.+?)<\/b>/';

            $replacement = preg_replace($pat, '##bold##$1##endbold##', $replacement);

            $replacement = preg_replace('/<strong>(.+?)<\/strong>/', '##bold##$1##endbold##', $replacement);

            $replacement = trim(strip_tags($replacement));

            $replacement = preg_replace('/##bold##(.*?)##endbold##/', '<options=bold>$1</>', $replacement);

            $converted[$key] = $replacement;
        }

        if ($stringPassed) {
            return $converted[0];
        }

        return $converted;
    }

    public function isLastMenu($data)
    {
        return self::APP_REQUEST_END == $data[$this->requestTypeParameter];
    }

    public function showErrors($response)
    {
        $this->error(['FATAL ERROR', '']);
        $errorKey = $response->isSuccess() ? 'data' : 'error';
        $error = $response->get($errorKey);
        $error = $this->htmlToText($error);
        $this->writeln($error);
        $this->error(['', 'END FATAL ERROR', '']);
    }

    public function showMetadataIfExists($data)
    {
        foreach ($this->simulatorMetadata as $metaName => $colorType) {
            if (isset($data[$metaName])) {
                $displayName = 'info' === $metaName ? 'debug' : $metaName;
                $displayName = strtoupper($displayName);
                $message = $this->htmlToText($data[$metaName]);

                $this->writeMetaName($colorType, [$displayName.' ON THIS MENU', '']);
                $this->writeln($message);
                $this->writeMetaName($colorType, ['', 'END '.$displayName.' ON THIS MENU']);
            }
        }
    }

    public function userWantsToDialAgain()
    {
        return $this->confirm('Do you want to dial again?');
    }

    public function ussdWantsUserResponse($data)
    {
        return self::APP_REQUEST_ASK_USER_RESPONSE == $data[
            $this->config('app.request_param_request_type')
        ];
    }

    public function wasSuccessfull($data)
    {
        foreach ($this->simulatorMetadata as $key => $value) {
            if (isset($data[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Write to console with the specified color.
     *
     * @param string          $colorType Can be info|error|question or any color name (Eg: green)
     * @param string|string[] $name      The string to write with colors
     *
     * @return void
     */
    public function writeMetaName($colorType, $name)
    {
        if (method_exists($this, $colorType)) {
            ($this->{$colorType}($name));
        } elseif (isset($this->colors[$colorType])) {
            $this->writeWithColor($name, $colorType);
        } else {
            $this->writeln($name);
        }
    }
}
