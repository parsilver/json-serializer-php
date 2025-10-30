<?php

use Farzai\JsonSerializer\Attributes\Discriminator;
use Farzai\JsonSerializer\Engine\DeserializerEngine;
use Farzai\JsonSerializer\Exceptions\TypeException;

describe('Polymorphic Deserialization Tests', function () {
    describe('Abstract Class with Discriminator', function () {
        it('deserializes to concrete class based on discriminator', function () {
            #[Discriminator(field: 'type', map: [
                'car' => PolyCar::class,
                'bike' => PolyBike::class,
            ])]
            abstract class PolyVehicle
            {
                public string $name;
            }

            class PolyCar extends PolyVehicle
            {
                public int $doors;
            }

            class PolyBike extends PolyVehicle
            {
                public bool $hasBasket;
            }

            $engine = new DeserializerEngine;

            // Deserialize to Car
            $carJson = json_encode(['type' => 'car', 'name' => 'Toyota', 'doors' => 4]);
            $car = $engine->deserializeToClass($carJson, PolyVehicle::class);

            expect($car)->toBeInstanceOf(PolyCar::class)
                ->and($car->name)->toBe('Toyota')
                ->and($car->doors)->toBe(4);

            // Deserialize to Bike
            $bikeJson = json_encode(['type' => 'bike', 'name' => 'Mountain', 'hasBasket' => true]);
            $bike = $engine->deserializeToClass($bikeJson, PolyVehicle::class);

            expect($bike)->toBeInstanceOf(PolyBike::class)
                ->and($bike->name)->toBe('Mountain')
                ->and($bike->hasBasket)->toBeTrue();
        });

        it('throws exception when discriminator field is missing', function () {
            #[Discriminator(field: 'type', map: [
                'car' => PolyCarMissing::class,
            ])]
            abstract class PolyVehicleMissing
            {
                public string $name;
            }

            class PolyCarMissing extends PolyVehicleMissing
            {
                public int $doors;
            }

            $engine = new DeserializerEngine;

            // JSON without discriminator field
            $json = json_encode(['name' => 'Toyota', 'doors' => 4]);

            expect(fn () => $engine->deserializeToClass($json, PolyVehicleMissing::class))
                ->toThrow(TypeException::class, "Discriminator field 'type' not found");
        });

        it('throws exception for unknown discriminator value', function () {
            #[Discriminator(field: 'type', map: [
                'car' => PolyCarUnknown::class,
            ])]
            abstract class PolyVehicleUnknown
            {
                public string $name;
            }

            class PolyCarUnknown extends PolyVehicleUnknown
            {
                public int $doors;
            }

            $engine = new DeserializerEngine;

            // JSON with unknown discriminator value
            $json = json_encode(['type' => 'plane', 'name' => 'Boeing']);

            expect(fn () => $engine->deserializeToClass($json, PolyVehicleUnknown::class))
                ->toThrow(TypeException::class, "Unknown discriminator value 'plane'");
        });
    });

    describe('Interface with Discriminator', function () {
        it('deserializes interface to concrete implementation', function () {
            #[Discriminator(field: 'paymentType', map: [
                'credit' => PolyCreditCard::class,
                'paypal' => PolyPayPal::class,
            ])]
            interface PolyPayment {}

            class PolyCreditCard implements PolyPayment
            {
                public string $cardNumber;

                public string $cvv;
            }

            class PolyPayPal implements PolyPayment
            {
                public string $email;
            }

            $engine = new DeserializerEngine;

            // Deserialize to CreditCard
            $creditJson = json_encode(['paymentType' => 'credit', 'cardNumber' => '1234', 'cvv' => '123']);
            $credit = $engine->deserializeToClass($creditJson, PolyPayment::class);

            expect($credit)->toBeInstanceOf(PolyCreditCard::class)
                ->and($credit->cardNumber)->toBe('1234')
                ->and($credit->cvv)->toBe('123');

            // Deserialize to PayPal
            $paypalJson = json_encode(['paymentType' => 'paypal', 'email' => 'user@example.com']);
            $paypal = $engine->deserializeToClass($paypalJson, PolyPayment::class);

            expect($paypal)->toBeInstanceOf(PolyPayPal::class)
                ->and($paypal->email)->toBe('user@example.com');
        });
    });

    describe('Nested Polymorphic Objects', function () {
        it('handles nested objects with discriminators', function () {
            #[Discriminator(field: 'engineType', map: [
                'electric' => PolyElectricEngine::class,
                'gasoline' => PolyGasolineEngine::class,
            ])]
            abstract class PolyEngine
            {
                public string $manufacturer;
            }

            class PolyElectricEngine extends PolyEngine
            {
                public int $batteryCapacity;
            }

            class PolyGasolineEngine extends PolyEngine
            {
                public float $displacement;
            }

            class PolyCarNested
            {
                public string $model;

                public PolyEngine $engine;
            }

            $engine = new DeserializerEngine;

            // Car with electric engine
            $json = json_encode([
                'model' => 'Tesla',
                'engine' => [
                    'engineType' => 'electric',
                    'manufacturer' => 'Tesla',
                    'batteryCapacity' => 100,
                ],
            ]);

            $car = $engine->deserializeToClass($json, PolyCarNested::class);

            expect($car->model)->toBe('Tesla')
                ->and($car->engine)->toBeInstanceOf(PolyElectricEngine::class)
                ->and($car->engine->manufacturer)->toBe('Tesla')
                ->and($car->engine->batteryCapacity)->toBe(100);
        });
    });

    describe('Direct Polymorphic Deserialization', function () {
        it('can deserialize directly to base class and resolve concrete type', function () {
            #[Discriminator(field: 'notificationType', map: [
                'email' => PolyEmailNotification::class,
                'sms' => PolySmsNotification::class,
            ])]
            abstract class PolyNotification
            {
                public string $message;
            }

            class PolyEmailNotification extends PolyNotification
            {
                public string $email;
            }

            class PolySmsNotification extends PolyNotification
            {
                public string $phoneNumber;
            }

            $engine = new DeserializerEngine;

            // Deserialize email notification
            $emailJson = json_encode([
                'notificationType' => 'email',
                'message' => 'Hello World',
                'email' => 'user@example.com',
            ]);

            $notification = $engine->deserializeToClass($emailJson, PolyNotification::class);

            expect($notification)->toBeInstanceOf(PolyEmailNotification::class)
                ->and($notification->message)->toBe('Hello World')
                ->and($notification->email)->toBe('user@example.com');

            // Deserialize SMS notification
            $smsJson = json_encode([
                'notificationType' => 'sms',
                'message' => 'Urgent Alert',
                'phoneNumber' => '+1234567890',
            ]);

            $sms = $engine->deserializeToClass($smsJson, PolyNotification::class);

            expect($sms)->toBeInstanceOf(PolySmsNotification::class)
                ->and($sms->message)->toBe('Urgent Alert')
                ->and($sms->phoneNumber)->toBe('+1234567890');
        });
    });

    describe('Custom Discriminator Field Names', function () {
        it('supports custom discriminator field names', function () {
            #[Discriminator(field: '__typename', map: [
                'Rectangle' => PolyRectangle::class,
                'Circle' => PolyCircle::class,
            ])]
            abstract class PolyShape
            {
                public string $color;
            }

            class PolyRectangle extends PolyShape
            {
                public float $width;

                public float $height;
            }

            class PolyCircle extends PolyShape
            {
                public float $radius;
            }

            $engine = new DeserializerEngine;

            // GraphQL-style __typename discriminator
            $json = json_encode(['__typename' => 'Rectangle', 'color' => 'red', 'width' => 10.5, 'height' => 5.2]);
            $shape = $engine->deserializeToClass($json, PolyShape::class);

            expect($shape)->toBeInstanceOf(PolyRectangle::class)
                ->and($shape->color)->toBe('red')
                ->and($shape->width)->toBe(10.5)
                ->and($shape->height)->toBe(5.2);
        });
    });

    describe('Multiple Inheritance Levels', function () {
        it('handles multi-level inheritance with discriminator', function () {
            #[Discriminator(field: 'deviceType', map: [
                'smartphone' => PolySmartphone::class,
                'tablet' => PolyTablet::class,
            ])]
            abstract class PolyDevice
            {
                public string $brand;
            }

            abstract class PolyMobileDevice extends PolyDevice
            {
                public int $batteryLife;
            }

            class PolySmartphone extends PolyMobileDevice
            {
                public string $carrierName;
            }

            class PolyTablet extends PolyMobileDevice
            {
                public float $screenSize;
            }

            $engine = new DeserializerEngine;

            $json = json_encode([
                'deviceType' => 'smartphone',
                'brand' => 'Apple',
                'batteryLife' => 24,
                'carrierName' => 'Verizon',
            ]);

            $device = $engine->deserializeToClass($json, PolyDevice::class);

            expect($device)->toBeInstanceOf(PolySmartphone::class)
                ->and($device->brand)->toBe('Apple')
                ->and($device->batteryLife)->toBe(24)
                ->and($device->carrierName)->toBe('Verizon');
        });
    });
});
