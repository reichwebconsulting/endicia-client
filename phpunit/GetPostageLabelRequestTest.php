<?php

namespace Tests\RWC\Endicia;

use PHPUnit\Framework\TestCase;
use RWC\Endicia\Address;
use RWC\Endicia\CertifiedIntermediary;
use RWC\Endicia\GetPostageLabelRequest;
use RWC\Endicia\InvalidArgumentException;
use RWC\Endicia\LabelImageFormat;
use RWC\Endicia\MailClass;

class GetPostageLabelRequestTest extends TestCase
{
	private $requesterId = 'lxxx';
	private $accountId = '25xxxxx';
	private $passPhrase = 'abcdefghijklmnopqrstuvwxyz1234567890';
	
	/**
	 *	A Double test to cover application level dependency
	 */
	public function testAddress(): void
    {
        $this->assertInstanceOf(
            Address::class,
            new Address('Jane Doe', NULL, '1 Hacker Way', NULL, 'Palo Alto', 'CA', '94025', NULL, 'US')
        );
    }
	
	/**
	 *	@depends testAddress
	 */
	public function testSimpleDomesticLabel() : void
	{
		$ci = CertifiedIntermediary::createFromCredentials(
			$this->accountId,
			$this->passPhrase
		);
		$to = new Address('Jane Doe', NULL, '1 Hacker Way', NULL, 'Palo Alto', 'CA', '94025', NULL, 'US');
		$from = new Address('John Doe', 'Endicia, Inc.', '278 Castro Street', NULL, 'Mountain View', 'CA', '94041', NULL, 'US');
		
		$labelRequest = new GetPostageLabelRequest(
			$this->requesterId,
			$ci,
			MailClass::PRIORITY,
			16,
			$from,
			$to
		);
		
		$this->assertInstanceOf(GetPostageLabelRequest::class, $labelRequest);
		
		/* XML representation of the "First Label Request" example from documentation */
		$expected = new \DOMDocument();
		$expected->load(__DIR__ . '/data/GetPostageLabelRequest/FirstLabelRequest.xml');
		
		$actual = new \DOMDocument();
		$actual->loadXml($labelRequest->toXml());
		$this->assertEqualXMLStructure($expected->firstChild, $actual->firstChild);
		
		//check node content
		$mailClassNodeList = $actual->getElementsByTagName('MailClass');
		$this->assertEquals($mailClassNodeList->length, 1);
		
		$mailClass = $mailClassNodeList[0]->textContent;
		$this->assertEquals($mailClass, 'Priority');
		
		$weightNodeList = $actual->getElementsByTagName('WeightOz');
		$this->assertEquals($weightNodeList->length, 1);
		
		$weight = $weightNodeList[0]->textContent;
		$this->assertEquals($weight, 16);
		
		$toNameNodeList = $actual->getElementsByTagName('ToName');
		$this->assertEquals($toNameNodeList->length, 1);
		
		$toName = $toNameNodeList[0]->textContent;
		$this->assertEquals($toName, 'Jane Doe');

		$toAddressNodeList = $actual->getElementsByTagName('ToAddress1');
		$this->assertEquals($toAddressNodeList->length, 1);
		
		$toAddress = $toAddressNodeList[0]->textContent;
		$this->assertEquals($toAddress, '1 Hacker Way');

		$toCityNodeList = $actual->getElementsByTagName('ToCity');
		$this->assertEquals($toCityNodeList->length, 1);
		
		$toCity = $toCityNodeList[0]->textContent;
		$this->assertEquals($toCity, 'Palo Alto');
		
		$toStateNodeList = $actual->getElementsByTagName('ToState');
		$this->assertEquals($toStateNodeList->length, 1);
		
		$toState = $toStateNodeList[0]->textContent;
		$this->assertEquals($toState, 'CA');
		
		$toPostalCodeNodeList = $actual->getElementsByTagName('ToPostalCode');
		$this->assertEquals($toPostalCodeNodeList->length, 1);
		
		$toPostalCode = $toPostalCodeNodeList[0]->textContent;
		$this->assertEquals($toPostalCode, '94025');

		$fromCompanyNodeList = $actual->getElementsByTagName('FromCompany');
		$this->assertEquals($fromCompanyNodeList->length, 1);
		
		$fromCompany = $fromCompanyNodeList[0]->textContent;
		$this->assertEquals($fromCompany, 'Endicia, Inc.');

		$fromNameNodeList = $actual->getElementsByTagName('FromName');
		$this->assertEquals($fromNameNodeList->length, 1);
		
		$fromName = $fromNameNodeList[0]->textContent;
		$this->assertEquals($fromName, 'John Doe');

		$fromAddressNodeList = $actual->getElementsByTagName('ReturnAddress1');
		$this->assertEquals($fromAddressNodeList->length, 1);
		
		$fromAddress = $fromAddressNodeList[0]->textContent;
		$this->assertEquals($fromAddress, '278 Castro Street');

		$fromCityNodeList = $actual->getElementsByTagName('FromCity');
		$this->assertEquals($fromCityNodeList->length, 1);
		
		$fromCity = $fromCityNodeList[0]->textContent;
		$this->assertEquals($fromCity, 'Mountain View');
		
		$fromStateNodeList = $actual->getElementsByTagName('FromState');
		$this->assertEquals($fromStateNodeList->length, 1);
		
		$fromState = $fromStateNodeList[0]->textContent;
		$this->assertEquals($fromState, 'CA');
		
		$fromPostalCodeNodeList = $actual->getElementsByTagName('FromPostalCode');
		$this->assertEquals($fromPostalCodeNodeList->length, 1);
		
		$fromPostalCode = $fromPostalCodeNodeList[0]->textContent;
		$this->assertEquals($fromPostalCode, '94041');
	}
	
	/**
	 *	@depends testAddress
	 *	@depends testSimpleDomesticLabel
	 */
	public function testTestRequest() : void
	{
		$ci = CertifiedIntermediary::createFromCredentials(
			$this->accountId,
			$this->passPhrase
		);
		$to = new Address('Jane Doe', NULL, '1 Hacker Way', NULL, 'Palo Alto', 'CA', '94025', NULL, 'US');
		$from = new Address('John Doe', 'Endicia, Inc.', '278 Castro Street', NULL, 'Mountain View', 'CA', '94041', NULL, 'US');
		
		$labelRequest = new GetPostageLabelRequest(
			$this->requesterId,
			$ci,
			MailClass::PRIORITY,
			16,
			$from,
			$to
		);
		
		// tested to here
		$xmlDocument = new \DOMDocument();
		$xmlDocument->loadXml($labelRequest->toXml());
		
		$this->assertThat($xmlDocument->firstChild->hasAttribute('Test'), $this->isFalse());
		
		$labelRequest->setTest(true);
		$this->assertTrue($labelRequest->isTest());
		
		$xmlDocument = new \DOMDocument();
		$xmlDocument->loadXml($labelRequest->toXml());
		
		$this->assertTrue($xmlDocument->firstChild->hasAttribute('Test'));
		$this->assertEquals($xmlDocument->firstChild->getAttribute('Test'), 'YES');
	}
	
	/**
	 *	@depends testAddress
	 *	@depends testSimpleDomesticLabel
	 */
	public function testRequestImageFormat() : void
	{
		$ci = CertifiedIntermediary::createFromCredentials(
			$this->accountId,
			$this->passPhrase
		);
		$to = new Address('Jane Doe', NULL, '1 Hacker Way', NULL, 'Palo Alto', 'CA', '94025', NULL, 'US');
		$from = new Address('John Doe', 'Endicia, Inc.', '278 Castro Street', NULL, 'Mountain View', 'CA', '94041', NULL, 'US');
		
		$labelRequest = new GetPostageLabelRequest(
			$this->requesterId,
			$ci,
			MailClass::PRIORITY,
			16,
			$from,
			$to
		);
		
		// tested to here
		$this->assertTrue($labelRequest->isImageFormatDefault());
		
		$xmlDocument = new \DOMDocument();
		$xmlDocument->loadXml($labelRequest->toXml());
		
		$this->assertThat($xmlDocument->firstChild->hasAttribute('ImageFormat'), $this->isFalse());
		
		$labelRequest->setImageFormat(LabelImageFormat::ZPLII);
		$this->assertThat($labelRequest->isImageFormatDefault(), $this->isFalse());
		$this->assertEquals($labelRequest->getImageFormat(), LabelImageFormat::ZPLII);
		
		$xmlDocument = new \DOMDocument();
		$xmlDocument->loadXml($labelRequest->toXml());
		
		$this->assertTrue($xmlDocument->firstChild->hasAttribute('ImageFormat'));
		$this->assertEquals($xmlDocument->firstChild->getAttribute('ImageFormat'), LabelImageFormat::ZPLII);
	}
}
