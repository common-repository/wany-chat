<?php

namespace ChatBot;


class ChB_Lang {

	private static array $_constants;

	public static function detectLang( $input_lang ) {

		if ( $input_lang === 'Vietnamese' || $input_lang === 'vi' ) {
			return 'vi';
		}

		if ( $input_lang === 'Italian' || $input_lang === 'it' ) {
			return 'it';
		}

		if ( $input_lang === 'Russian' || $input_lang === 'ru' ) {
			return 'ru';
		}

		if ( $input_lang === 'Georgian' || $input_lang === 'ka' ) {
			return 'ka';
		}

		if ( $input_lang === 'Spanish' || $input_lang === 'es' ) {
			return 'es';
		}

		if ( $input_lang === 'Portuguese' || $input_lang === 'pt' ) {
			return 'pt';
		}

		if ( $input_lang === 'French' || $input_lang === 'fr' ) {
			return 'fr';
		}

		if ( $input_lang === 'Arabic' || $input_lang === 'ar' ) {
			return 'ar';
		}

		return 'en';
	}

	/**
	 * @return array
	 */
	public static function getLangConstants() {
		if ( ! isset( self::$_constants ) ) {
			try {
				$class_reflex     = new \ReflectionClass( '\ChatBot\ChB_Lang' );
				self::$_constants = $class_reflex->getConstants();
			} catch ( \Exception $e ) {
				self::$_constants = [];
			}
		}

		return self::$_constants;
	}

	public static function translateByKey( $key ) {

		if ( array_key_exists( $key, self::getLangConstants() ) ) {
			return ChB_Lang::translate( self::getLangConstants()[ $key ] );
		}

		return '';
	}

	public static function translateUsingHook( $hook ) {

		return self::translate( apply_filters( $hook, '' ) );
	}


	public static function getKeyByPhrase( $phrase ) {
		return empty( $phrase[0] ) ? null : $phrase[0];
	}

	public static function convertAssoc( $phrase ) {
		if ( is_array( $phrase ) ) {
			if ( isset( $phrase['en'] ) ) {
				$phrase[2] = $phrase['en'];
			}
			if ( isset( $phrase['vi'] ) ) {
				$phrase[3] = $phrase['vi'];
			}
			if ( isset( $phrase['ru'] ) ) {
				$phrase[4] = $phrase['ru'];
			}
			if ( isset( $phrase['ka'] ) ) {
				$phrase[5] = $phrase['ka'];
			}
			if ( isset( $phrase['it'] ) ) {
				$phrase[6] = $phrase['it'];
			}
			if ( isset( $phrase['es'] ) ) {
				$phrase[7] = $phrase['es'];
			}
			if ( isset( $phrase['pt'] ) ) {
				$phrase[8] = $phrase['pt'];
			}

			if ( isset( $phrase['fr'] ) ) {
				$phrase[9] = $phrase['fr'];
			}

			if ( isset( $phrase['ar'] ) ) {
				$phrase[10] = $phrase['ar'];
			}

			// if en is empty - setting it to any non-empty translation
			if ( ! isset( $phrase[2] ) ) {
				foreach ( $phrase as $ind => $val ) {
					if ( is_int( $ind ) && $ind > 2 && $val ) {
						$phrase[2] = $val;
					}
				}
			}
		}

		return $phrase;
	}

	/**
	 * @param $phrase string|array string is returned as is, array is used for choosing translation
	 *
	 * @return mixed|string
	 */
	public static function translate( $phrase ) {

		if ( is_string( $phrase ) ) {
			return $phrase;
		}

		if ( ! empty( $phrase[0] ) && ! empty( $phrase[1] ) ) {
			$custom_translation = ChB_CustomLang::getCustomTranslation( $phrase[0], ChB_Settings()->lang );
			if ( $custom_translation ) {
				return $custom_translation;
			}
		}

		if ( ( ChB_Settings()->lang === 'vn' || ChB_Settings()->lang === 'vi' ) && ! empty( $phrase[3] ) ) {
			return $phrase[3];
		} elseif ( ChB_Settings()->lang === 'ru' && ! empty( $phrase[4] ) ) {
			return $phrase[4];
		} elseif ( ChB_Settings()->lang === 'ka' && ! empty( $phrase[5] ) ) {
			return $phrase[5];
		} elseif ( ChB_Settings()->lang === 'it' && ! empty( $phrase[6] ) ) {
			return $phrase[6];
		} elseif ( ChB_Settings()->lang === 'es' && ! empty( $phrase[7] ) ) {
			return $phrase[7];
		} elseif ( ChB_Settings()->lang === 'pt' && ! empty( $phrase[8] ) ) {
			return $phrase[8];
		} elseif ( ChB_Settings()->lang === 'fr' && ! empty( $phrase[9] ) ) {
			return $phrase[9];
		} elseif ( ChB_Settings()->lang === 'ar' && ! empty( $phrase[10] ) ) {
			return $phrase[10];
		} elseif ( ! empty( $phrase[2] ) ) {
			return $phrase[2];
		} else {
			return '';
		}
	}

	public static function translateWithNumber( $phrase, $number ) {
		$res = self::translate( $phrase );
		if ( is_array( $res ) ) {
			if ( ChB_Settings()->lang === 'ru' ) {
				//$phrase = [$for1, $for2, $for5]
				$number      = intval( $number );
				$last2digits = $number % 100;
				if ( $last2digits === 11 || $last2digits === 12 || $last2digits === 13 || $last2digits === 14 ) {
					return $res[2];
				} else {
					$lastdigit = $number % 10;
					if ( $lastdigit === 1 ) {
						return $res[0];
					} elseif ( $lastdigit === 2 || $lastdigit === 3 || $lastdigit === 4 ) {
						return $res[1];
					} else {
						return $res[2];
					}
				}
			} else {
				//$phrase = [$for1, $for2]
				return intval( $number ) === 1 ? $res[0] : $res[1];
			}
		} else {
			return $res;
		}
	}

	public static function langIsRTL() {
		return ( ChB_Settings()->lang === 'ar' );
	}

	public static function maybeForceLTR( $str ) {
		if ( self::langIsRTL() ) {
			return "\u{200E}" . $str . "\u{200E}";//U+200E (LEFT-TO-RIGHT MARK)
		}

		return $str;
	}

	public static function maybeForceRTL( $str ) {
		if ( self::langIsRTL() ) {
			return "\u{200F}" . $str . "\u{200F}";//U+200F (RIGHT-TO-LEFT MARK)
		}

		return $str;
	}

	/**
	 * @param $phrase string|array string is used as is, array is used for choosing translation
	 * @param $par1
	 * @param null $par2
	 *
	 * @return mixed|string|string[]
	 */
	public static function translateWithPars( $phrase, $par1, $par2 = null ) {

		if ( $par2 ) {
			$res = str_replace( [ '%s1', '%s2' ], [ $par1, $par2 ], ChB_Lang::translate( $phrase ) );
		} else {
			$res = str_replace( '%s1', $par1, ChB_Lang::translate( $phrase ) );
		}

		if ( self::langIsRTL() ) {
			return "\u{200F}" . $res . "\u{200F}";
		}

		return $res;
	}

	public static function getUsedLanguages() {
		if ( ! ( $used_languages = ChB_Settings()->getParam( 'used_languages' ) ) ) {
			$used_languages = [ 'en', 'es', 'ru' ];
		}

		return $used_languages;
	}

	const LANGS = [
		'en' => [
			'caption' => 'English 🇬🇧',
			'flag'    => '🇬🇧',
			'text1'   => 'Please choose language',
			'text2'   => '%s1 - 🇬🇧 to change language',
			'ind'     => 2
		],
		'ru' => [
			'caption' => 'Русский 🇷🇺',
			'flag'    => '🇷🇺',
			'text1'   => 'Пожалуйста выберите язык',
			'text2'   => '%s1 - 🇷🇺 чтобы изменить язык',
			'ind'     => 4
		],
		'vi' => [
			'caption' => 'Tiếng Việt 🇻🇳',
			'flag'    => '🇻🇳',
			'text1'   => 'Vui lòng chọn ngôn ngữ',
			'text2'   => '%s1 - 🇻🇳 thay đổi ngôn ngữ',
			'ind'     => 3
		],
		'ka' => [
			'caption' => 'ქართული 🇬🇪',
			'flag'    => '🇬🇪',
			'text1'   => 'გთხოვთ, აირჩიოთ ენა',
			'text2'   => '%s1 - 🇬🇪 ენის შესაცვლელად',
			'ind'     => 5
		],
		'it' => [
			'caption' => 'Italiana 🇮🇹',
			'flag'    => '🇮🇹',
			'text1'   => 'Si prega di scegliere la lingua',
			'text2'   => '%s1 - 🇮🇹 per cambiare lingua',
			'ind'     => 6
		],
		'es' => [
			'caption' => 'Español 🇪🇸',
			'flag'    => '🇪🇸',
			'text1'   => 'Por favor elige idioma',
			'text2'   => '%s1 - 🇪🇸 para cambiar el idioma',
			'ind'     => 7
		],
		'pt' => [
			'caption' => 'Português 🇵🇹',
			'flag'    => '🇵🇹',
			'text1'   => 'Por favor escolha o idioma',
			'text2'   => '%s1 - 🇵🇹 para mudar o idioma',
			'ind'     => 8
		],
		'fr' => [
			'caption' => 'Français 🇫🇷',
			'flag'    => '🇫🇷',
			'text1'   => 'Veuillez choisir la langue',
			'text2'   => '%s1 - 🇫🇷 pour changer de langue',
			'ind'     => 9
		],
		'ar' => [
			'caption' => 'عربي 🇦🇪',
			'flag'    => '🇦🇪',
			'text1'   => 'الرجاء اختيار اللغة',
			'text2'   => '%s1 - 🇦🇪 لتغيير اللغة',
			'ind'     => 10
		],
	];

	const LNG0001 = [
		'LNG0001',
		1,
		'Please choose size:',
		'Vui lòng chọn kích thước:',
		'Пожалуйста, выберите размер:',
		'გთხოვთ აირჩიოთ ზომა:',
		'Per favore scegli la taglia:',
		'Elige tu talle:',
		'Por favor, escolha o tamanho:',
		'Veuillez choisir une taille:',
		'الرجاء اختيار الحجم:'
	];
	const LNG0002 = [
		'LNG0002',
		1,
		'Size',
		'Kích thước',
		'Размер',
		'ზომა',
		'Taglia',
		'Talle',
		'Tamanho',
		'Taille',
		'الحجم'
	];
	const LNG0123 = [
		'LNG0123',
		1,
		'👉 Choose this option',
		'👉 Chọn tùy chọn này',
		'👉 Выбрать этот вариант',
		'👉 ავირჩიო ეს ვარიანტი',
		'👉 Scegli questa opzione',
		'👉 Elige esta opción',
		'👉 Escolha esta opção',
		'👉 Choisissez cette option',
		'👈 اختار هذا الخيار'
	];
	const LNG0003 = [
		'LNG0003',
		1,
		'Oops, wrong number :)',
		'Rất tiếc, nhầm số :)',
		'Некорректный номер :)',
		'უი, არასწორი ნომერი :)',
		'Oops, numero sbagliato :)',
		'Oops, número equivocado :)',
		'Ops, número errado :)',
		'Oups, mauvais numéro :)',
		'عفواً، الرقم خطأ :)'
	];

	const LNG0181 = [
		'LNG0181',
		1,
		'Oops, something went wrong 🤔',
		'Rất tiếc, đã xảy ra lỗi 🤔',
		'Ой, что-то пошло не так 🤔',
		'უი, რაღაც შეფერხდა 🤔',
		'Ops, qualcosa è andato storto 🤔',
		'Oops, algo salió mal 🤔',
		'Ops, algo deu errado 🤔',
		'Oups, quelque chose s\'est mal passé 🤔',
		'عفواًً، هنالك خطأ 🤔'
	];

	const LNG0004 = [
		'LNG0004',
		1,
		'☎ Your phone:',
		'☎ Số điện thoại của bạn:',
		'☎ Ваш номер телефона:',
		'☎ თქვენი ტელეფონის ნომერია:',
		'☎ Il tuo numero di telefono:',
		'☎ Tu número:',
		'☎ Seu número de telefone:',
		'☎ Votre numéro de téléphone:',
		'☎ الرجاء إختيار رقم هاتفك :'
	];
	const LNG0165 = [
		'LNG0165',
		1,
		'📧 Your email:',
		'📧 Email của bạn:',
		'📧 Ваш email:',
		'📧 თქვენი ელფოსტა არის:',
		'📧 La tua email:',
		'📧 Tu correo electrónico:',
		'📧 Seu e-mail:',
		'📧 Votre email:',
		'📧 بريدك الإلكتروني :'
	];

	const LNG0010 = [
		'LNG0010',
		1,
		'🏠 Your address:',
		'🏠 Địa chỉ của bạn:',
		'🏠 Ваш адрес:',
		'🏠 თქვენი მისამართია:',
		'🏠 Il tuo indirizzo:',
		'🏠 Tu dirección:',
		'🏠 Seu endereço:',
		'🏠 Votre adresse:',
		'🏠 العنوان:'
	];
	const LNG0005 = [
		'LNG0005',
		1,
		'Please choose',
		'Vui lòng chọn',
		'Пожалуйста, выберите',
		'გთხოვთ, აირჩიოთ',
		'Per favore scegli',
		'Elija',
		'Por favor escolha',
		'Veuillez choisir',
		'الرجاء الإختيار'
	];
	const LNG0006 = [
		'LNG0006',
		1,
		'👉 Yes ✔',
		'👉 Có ✔',
		'👉 Да ✔',
		'👉 დიახ ✔',
		'👉 Si ✔',
		'👉 Si ✔',
		'👉 Sim ✔',
		'👆 Oui ✔',
		'👈 نعم ✔'
	];
	const LNG0007 = [
		'LNG0007',
		1,
		'👉 No ❌',
		'👉 Không ❌',
		'👉 Нет ❌',
		'👉 არა ❌',
		'👉 No ❌',
		'👉 No ❌',
		'👉 Não ❌',
		'👆 Non ❌',
		'👈 لا ❌'
	];
	const LNG0008 = [
		'LNG0008',
		1,
		'Language is set to English 🇬🇧',
		'Ngôn ngữ được đặt thành tiếng Việt 🇻🇳',
		'Вы переключились на Русский язык 🇷🇺',
		'ენა დაყენებულია ქართულზე 🇬🇪',
		'La lingua predefinita è Italiana 🇮🇹',
		'Se cambio el idioma al Español 🇪🇸',
		'O idioma está definido para Português 🇵🇹',
		'La langue est réglée sur Français 🇫🇷',
		'تم تعيين اللغة العربية كـ لغة اساسية 🇦🇪'
	];
	const LNG0009 = [
		'LNG0009',
		1,
		'Just type:',
		'Chỉ loại:',
		'Просто наберите слово:',
		'უბრალოდ ჩაწერეთ:',
		'Basta digitare:',
		'Escribe:',
		'Basta digitar:',
		'Tapez juste:',
		'فقط إكتب:'
	];
	const LNG0011 = [
		'LNG0011',
		1,
		'Hi!',
		'Chào!',
		'Приветствую!',
		'გამარჯობა!',
		'Ciao!',
		'Hola!',
		'Oi!',
		'Salut!',
		'مرحباً!'
	];
	const LNG0012 = [
		'LNG0012',
		1,
		'Hi, %s1!',
		'Chào %s1!',
		'Приветствую, %s1!',
		'გამარჯობა %s1!',
		'Ciao, %s1!',
		'Hola, %s1!',
		'Olá, %s1!',
		'Salut, %s1!',
		'مرحباً, %s1!'
	];
	const LNG0013 = [
		'LNG0013',
		1,
		'or..',
		'hoặc là..',
		'или..',
		'ან..',
		'oppure..',
		'o..',
		'ou..',
		'ou...',
		'أو..'
	];
	const LNG0014 = [
		'LNG0014',
		1,
		'👆 See more',
		'👆 Xem thêm',
		'👆 Раскрыть',
		'👆 იხილეთ მეტი',
		'👆 Vedi altro',
		'👆 Ver más',
		'👆 Ver mais',
		'👆 Voir plus',
		'👆 شاهد المزيد'
	];
	const LNG0015 = [
		'LNG0015',
		1,
		'to shop inside this bot',
		'để mua sắm bên trong bot này',
		'чтобы открыть каталог',
		'ვიყიდოთ ამ ბოტის შიგნით',
		"per fare acquisti all'interno di questo BOT",
		'comprar a través del asistente virtual',
		'para fazer compras dentro deste bot',
		'pour faire du shopping via ce bot',
		'للتسوق داخل المحادثة'
	];

	const LNG0016 = [
		'LNG0016',
		1,
		'to get your orders',
		'xem lại đơn đặt hàng của bạn',
		'чтобы открыть список заказов',
		'თქვენი შეკვეთების მისაღებად',
		'per ricevere i tuoi ordini',
		'para recibir tu pedido',
		'para receber seus pedidos',
		'pour obtenir vos commandes',
		'للحصول على طلباتك'
	];

	const LNG0017 = [
		'LNG0017',
		1,
		'👆 Orders',
		'👆 Đơn đặt hàng',
		'👆 Мои заказы',
		'👆 შეკვეთები',
		'👆 Ordini',
		'👆 Pedidos',
		'👆 Encomendas',
		'👆 Commandes',
		'👆 الطلبات'
	];
	const LNG0018 = [
		'LNG0018',
		1,
		'Shop now',
		'Mua sắm ngay bây giờ',
		'В магазин!',
		'მაღაზიაში!',
		'Acquista ora!',
		'Comprar ahora!',
		'Compre agora!',
		'Acheter maintenant',
		'تسوق الأن'
	];
	const LNG0019 = [
		'LNG0019',
		1,
		'🚚 Shipping from',
		'🚚 Phí vận chuyển từ',
		'🚚 Доставка от',
		'🚚 მიწოდება დან',
		'🚚 Spedizione a partire da',
		'🚚 Envío desde',
		'🚚 Envio de',
		'🚚 Expédition à partir de',
		'🚚 الشحن من '
	];
	const LNG0020 = [
		'LNG0020',
		1,
		'Quantity:',
		'Số lượng:',
		'Количество:',
		'რაოდენობა:',
		'Quantità:',
		'Cantidad:',
		'Quantidade:',
		'Quantité:',
		'العدد:'
	];
	const LNG0022 = [
		'LNG0022',
		1,
		'Sum:',
		'Tổng phụ:',
		'Сумма:',
		'ჯამი:',
		'Somma:',
		'Total:',
		'Soma:',
		'Somme:',
		'المجموع:'
	];
	const LNG0023 = [
		'LNG0023',
		1,
		'🚚 Shipping',
		'🚚 Vận chuyển',
		'🚚 Доставка',
		'🚚 მიწოდება',
		'🚚 Spedizione',
		'🚚 Envío',
		'🚚 Envio',
		'🚚 Expédition',
		'🚚 الشحن'
	];
	const LNG0024 = [
		'LNG0024',
		1,
		'👉 SUM:',
		'👉 TỔNG SỐ:',
		"👉 ИТОГО:",
		'👉 ჯამი:',
		'👉 SOMMARIO:',
		'👉 TOTAL:',
		'👉 SOMA:',
		'👉 SOMME:',
		'👈 المجموع:'
	];
	const LNG0025 = [
		'LNG0025',
		1,
		'👆 I confirm order ❤📦',
		'👆 Xác nhận đơn ❤ 📦',
		'👆 Подтвердить заказ ❤📦',
		'👆 ვადასტურებ შეკვეთას ❤ 📦',
		'👆 Confermo l\'ordine ❤ 📦',
		'👆 Confirmar orden ❤ 📦',
		'👆 Eu confirmo o pedido ❤📦',
		'👆 Je confirme la commande ❤📦',
		'👆 انا أؤكد الطلبية ❤📦'
	];

	const LNG0174 = [
		'LNG0174',
		1,
		'🌍 I confirm order',
		'🌍 Xác nhận đơn',
		'🌍 Подтвердить заказ',
		'🌍 ვადასტურებ შეკვეთას',
		'🌍 Confermo l\'ordine',
		'🌍 Confirmar orden',
		'🌍 Eu confirmo o pedido',
		'🌍 Je confirme la commande',
		'🌍 انا أؤكد الطلبية'
	];

	const LNG0026 = [
		'LNG0026',
		1,
		'Change order',
		'Thay đổi đơn',
		'Изменить заказ',
		'Შეკვეთის შეცვლა',
		'Modifica ordine',
		'Cambiar Orden',
		'Mudar o pedido',
		'Modifier la commande',
		'تغيير الطلب'
	];
	const LNG0027 = [
		'LNG0027',
		0,
		'Cancel',
		'Hủy bỏ',
		'Отмена'
	];
	const LNG0029 = [
		'LNG0029',
		1,
		'Success! 👌',
		'Thành công! 👌',
		'Готово! 👌',
		'შესრულდა! 👌',
		'Fatto! 👌',
		'Realizado con éxito! 👌',
		'Sucesso! 👌',
		'Succès ! 👌',
		'تمت العملية بنجاح! 👌'
	];
	const LNG0030 = [
		'LNG0030',
		1,
		'Your order📦 number is: %s1',
		'Số đơn đặt hàng📦 của bạn là: %s1',
		'Номер Вашего заказа📦: %s1',
		'📦თქვენი შეკვეთის ნომერია: %s1',
		'Il tuo numero d\'ordine📦 è: %s1',
		'📦 Tu número de orden es: %s1',
		'O número do seu pedido📦 é: %s1',
		'Votre numéro de commande📦 est: %s1',
		'عدد طلباتك 📦 هو: %s1'
	];
	const LNG0031 = [
		'LNG0031',
		1,
		'We will contact soon to confirm delivery 🚚',
		'Chúng tôi sẽ sớm liên hệ để xác nhận việc giao hàng 🚚',
		'Мы свяжемся с Вами в ближайшее время для подтверждения доставки 🚚',
		'ჩვენი წარმომადგენელი დაგიკავშირდებათ ძალიან მალე <3',
		'Ti contatteremo entro 1 giorno lavorativo per confermare la consegna 🚚',
		'En menos de 24hs laborales te contactaremos para confirmar tu envío 🚚',
		'Entraremos em contato em breve para confirmar a entrega 🚚',
		'Nous vous contacterons bientôt pour confirmer la livraison 🚚',
		'سوف نتواصل معك قريباً لتأكيد الطلب 🚚'
	];
	const LNG0032 = [
		'LNG0032',
		1,
		'Shop more ☺',
		'Mua sắm nhiều hơn☺',
		'ЕЩЁ ТОВАРЫ!☺',
		'შეიძინეთ მეტი ☺',
		'Acquista di più ☺',
		'Ver más ☺',
		'Comprar mais ☺',
		'Continuer le shopping ☺',
		'تسوق المزيد ☺'
	];
	const LNG0033 = [
		'LNG0033',
		0,
		'GET MORE PRODUCTS',
		'NHẬN THÊM SẢN PHẨM',
		''
	];
	const LNG0034 = [
		'LNG0034',
		0,
		'Sorry... No available sizes. Please try again later!',
		'Xin lỗi ... Size này hiện tại không có sẵn. Vui lòng thử lại sau!',
		'Ой :) ... Нет доступных размеров. Пожалуйста, попробуйте позже!',
		''
	];
	const LNG0035 = [
		'LNG0035',
		1,
		"You haven't done any orders yet ;)",
		'Bạn chưa thực hiện bất kỳ đơn đặt hàng nào;)',
		'У Вас еще нет заказов ;)',
		'შეკვეთა ჯერ არ გაგიკეთებიათ ;)',
		'Non hai ancora fatto ordini ;)',
		'No has hecho ningun pedido todavía ;)',
		'Você ainda não fez nenhuma encomenda ;)',
		'Vous n\'avez pas encore passé de commande ;)',
		'لم تقم بعمل اي طلبية بعد ;)'
	];
	const LNG0036 = [
		'LNG0036',
		1,
		'Order',
		'Đơn đặt hàng',
		'Заказ',
		'შეკვეთა',
		'Ordine',
		'Pedido',
		'Encomendas',
		'Commander',
		'الطلب'
	];
	const LNG0037 = [
		'LNG0037',
		1,
		'Your orders 📦: ',
		'Đơn hàng của bạn📦:',
		'Ваши заказы 📦: ',
		'თქვენი შეკვეთები 📦:',
		'I tuoi ordini 📦:',
		'Tus pedidos 📦:',
		'Seus pedidos 📦:',
		'Vos commandes 📦:',
		'طلباتك📦:'
	];
	const LNG0038 = [
		'LNG0038',
		0,
		'One moment ⏳',
		'Một lát⏳'
	];
	const LNG0039 = [
		'LNG0039',
		0,
		'We are preparing your order 📦',
		'Chúng tôi đang chuẩn bị đơn đặt hàng của bạn📦'
	];
	const LNG0040 = [
		'LNG0040',
		1,
		'Sorry... No available stock. Please try again later!',
		'Xin lỗi ... Không có sẵn trong kho. Vui lòng thử lại sau!',
		'Ой :) ... Товара нет на складе. Пожалуйста, попробуйте позже!',
		'უკაცრავად... მარაგი არ არის ხელმისაწვდომი. Გთხოვთ სცადოთ მოგვიანებით!',
		'Spiacente... Non c’è disponibilità. Si prega di riprovare più tardi!',
		'Disculpe... stock no disponible. Por favor, inténtelo de nuevo más tarde!',
		'Desculpe... Sem estoque disponível. Por favor, tente novamente mais tarde!',
		'Désolé... Aucun stock disponible. Veuillez réessayer plus tard!',
		'نعتذر ... لا يوجد كمية كافية. حاول لاحقاً!'
	];
	const LNG0041 = [
		'LNG0041',
		1,
		'Please choose quantity:',
		'Vui lòng chọn số lượng:',
		'Пожалуйста, выберите количество',
		'გთხოვთ, აირჩიოთ რაოდენობა:',
		'Scegli la quantità:',
		'Elija la cantidad:',
		'Por favor, escolha a quantidade:',
		'Veuillez choisir la quantité:',
		'الرجاء إختيار الكمية:'
	];
	const LNG0043 = [
		'LNG0043',
		1,
		'Our consultant 👩 will get to you as soon as possible 🚀',
		'👩Chuyên gia tư vấn của chúng tôi sẽ trả lời bạn nhanh nhất có thể 🚀',
		'Наш консультант 👩 скоро 🚀свяжется с Вами',
		'ჩვენი კონსულტანტი 👩 დაგიკავშირდებათ რაც შეიძლება მალე 🚀',
		'Il nostro consulente 👩 ti raggiungerà il prima possibile 🚀',
		'Un vendedor 👩 se comunicará con usted en breve 🚀',
		'Nosso atendedor 👩 chegará até você o mais rápido possível 🚀',
		'Notre consultant 👩 vous répondra dans les plus brefs délais 🚀',
		'موظفنا 👩 سوف يتواصل معك في اسرع وقت🚀'
	];
	const LNG0044 = [
		'LNG0044',
		1,
		'Please stay in touch 📱! ',
		'Hãy giữ liên lạc📱!',
		'Будьте на связи 📱! ',
		'გთხოვთ დარჩეთ კონტაქტზე 📱',
		'Per favore rimani in contatto 📱',
		'Por favor, estamos en contacto 📱',
		'Por favor, fique em contato 📱!',
		'S\'il vous plaît, restez en contact 📱!',
		'رجاءً، إبقى بالقرب 📱!'
	];
	const LNG0042 = [
		'LNG0042',
		1,
		'👆 BUY ❤',
		'👆 MUA ❤',
		'👆 КУПИТЬ ❤',
		'👆 ყიდვა ❤',
		'👆 ACQUISTA ❤',
		'👆 COMPRAR ❤',
		'👆 COMPRE ❤',
		'👆 ACHETER ❤',
		'👆 شراء❤'
	];

	const LNG0177 = [
		'LNG0177',
		1,
		'🌍 BUY',
		'🌍 MUA',
		'🌍 КУПИТЬ',
		'🌍 ყიდვა',
		'🌍 ACQUISTA',
		'🌍 COMPRAR',
		'🌍 COMPRE',
		'🌍 ACHETER',
		'🌍 شراء'
	];

	const LNG0045 = [
		'LNG0045',
		1,
		'👉 BUY SIZE %s1',
		'👉 MUA SIZE %s1',
		'👉 КУПИТЬ РАЗМЕР %s1',
		'👉 შეიძინეთ %s1 ზომა',
		'👉 ACQUISTA LA TAGLIA %s1',
		'👉 COMPRAR TALLE %s1',
		'👉 COMPRE TAMANHO %s1',
		'👉 ACHETER LA TAILLE %s1',
		'👈 شراء حجم %s1'
	];
	const LNG0046 = [
		'LNG0046',
		1,
		'👆 Open',
		'👆 Mở',
		'👆 Открыть',
		'👆 გახსენით',
		'👆 Apri',
		'👆 Elegir',
		'👆 Aberto',
		'👆 Ouvrir',
		'👆 تصفح'
	];
	const LNG0176 = [
		'LNG0176',
		1,
		'👉 ',
		'👉 ',
		'👉 ',
		'👉 ',
		'👉 ',
		'👉 ',
		'👉 ',
		'👉 ',
		'👉 ',//' 👈'
	];
	const LNG0175 = [
		'LNG0175',
		1,
		'🌍 Open',
		'🌍 Mở',
		'🌍 Открыть',
		'🌍 გახსენით',
		'🌍 Apri',
		'🌍 Elegir',
		'🌍 Aberto',
		'🌍 Ouvrir',
		'🌍 تصفح'
	];
	const LNG0047 = [
		'LNG0047',
		1,
		'👆 Shop',
		'👆 Mua',
		'👆 Купить',
		'👆 გახსენით',
		'👆 Acquista',
		'👆 Comprar',
		'👆 Loja',
		'👆 Boutique',
		'👆 تسوق'
	]; //to shop
	const LNG0048 = [
		'LNG0048',
		1,
		'Shop all',
		'Mua tất cả',
		'Все товары',
		'ყველა საქონელი',
		'Tutti i prodotti',
		'Comprar todo',
		'Comprar tudo',
		'Tous les produits',
		'تسوق كافة المنتجات'
	];
	const LNG0049 = [
		'LNG0049',
		0,
		'.. or you can take a look at other products 🎁',
		'.. hoặc bạn có thể xem các sản phẩm khác 🎁',
		'.. еще товары 🎁',
		''
	];
	const LNG0050 = [
		'LNG0050',
		0,
		'Get all products from',
		'Nhận tất cả sản phẩm từ',
		'Открыть все товары от'
	];
	const LNG0051 = [
		'LNG0051',
		0,
		'👉 NEW PRICE: ',
		'👉 GIÁ MỚI: ',
		"ЦЕНА СО СКИДКОЙ:\n👉 "
	];
	const LNG0052 = [
		'LNG0052',
		0,
		'👉 OLD PRICE: ',
		'👉 GIÁ CŨ: ',
		"👉 ЦЕНА: "
	];
	const LNG0053 = [
		'LNG0053',
		1,
		'till %s1',
		'cho đến %s1',
		'до %s1',
		'%s1 წლამდე',
		'fino al %s1',
		'hasta el %s1',
		'até %s1',
		'jusqu\'à %s1',
		'حتى %s1'
	];
	const LNG0054 = [
		'LNG0054',
		1,
		'Old sum:',
		'Tổng phụ cũ:',
		"👉 Cумма:",
		'ძველი ჯამი:',
		'Vecchia somma:',
		'Total anterior:',
		'Soma antiga:',
		'Ancienne somme:',
		'المجموع القديم:'
	];
	const LNG0055 = [
		'LNG0055',
		1,
		'New sum:',
		'Tổng phụ mới:',
		"Cумма со скидкой:\n👉",
		'ახალი ჯამი:',
		'Nuova somma:',
		'Nuevo total:',
		'Nova soma:',
		'Nouvelle somme:',
		'المجموع الجديد:'
	];
	const LNG0056 = [
		'LNG0056',
		1,
		'👉 OLD SUM:',
		'👉 TỔNG CŨ:',
		"👉 СУММА:",
		'👉 ძველი ჯამი:',
		"👉 VECCHIA SOMMA:\n👉",
		"👉 TOTAL ANTERIOR:\n👉",
		'👉 SOMA ANTIGA:',
		'👉 ANCIENNE SOMME:',
		'👈 المجموع القديم:'
	];
	const LNG0057 = [
		'LNG0057',
		1,
		'👉 NEW SUM:',
		'👉 TỔNG MỚI:',
		"👉 СУММА СО СКИДКОЙ:\n👉",
		'👉 ახალი ჯამი:',
		"👉 NUOVA SOMMA:\n👉",
		"👉 NUEVO TOTAL:\n👉",
		'👉 NOVA SOMA:',
		'👉 NOUVELLE SOMME:',
		'👈 المجموع الجديد:'
	];
	const LNG0058 = [
		'LNG0058',
		0,
		'Hey, %s1! We\'ve got a DISCOUNT for you!',
		'Xin chào, %s1! Chúng tôi đã GIẢM GIÁ cho bạn!',
		'%s1! Мы приготовили для Вас СКИДКУ!',
		'',
		'',
		'',
		'Ei, %s1! Temos um DESCONTO para você!',
		'Hé, %s1 ! Nous avons une RÉDUCTION pour vous !'
	];
	const LNG0059 = [
		'LNG0059',
		1,
		'%s1% DISCOUNT on ALL items till %s2',
		'%s1% GIẢM GIÁ cho tất cả các mặt hàng cho đến %s2',
		'СКИДКА %s1% на ВСЕ товары до %s2',
		'',
		'',
		'',
		'%s1% DESCONTO em TODOS os itens até %s2',
		'%s1% DE REMISE sur TOUS les articles jusqu\'à %s2',
		's1% % خصم على كافة المنتجات حتى %s2'
	];
	const LNG0060 = [
		'LNG0060',
		1,
		'Available colors:',
		'Màu sắc có sẵn:',
		'Цвета:',
		'ხელმისაწვდომი ფერები:',
		'Colori disponibili:',
		'Colores disponibles:',
		'Cores disponiveis:',
		'Couleurs disponibles:',
		'الألوان المتاحة:'
	];
	const LNG0061 = [
		'LNG0061',
		0,
		'one color',
		'một màu',
		'один цвет'
	];
	const LNG0062 = [
		'LNG0062',
		1,
		'❓ If you have any questions about this product. You can talk to our consultant 👩',
		'❓ Nếu bạn có bất kỳ câu hỏi nào về sản phẩm này. Bạn có thể nói chuyện với chuyên gia tư vấn của chúng tôi 👩',
		"❓ Возникли вопросы?\nНаш консультант 👩 будет рад помочь Вам!",
		'❓ თუ თქვენ გაქვთ რაიმე შეკითხვები ამ პროდუქტთან დაკავშირებით. შეგიძლიათ ესაუბროთ ჩვენს კონსულტანტს 👩',
		'❓ Se hai qualche domanda su questo prodotto. Puoi parlare con il nostro consulente 👩',
		'❓ Si tienes preguntas, puedes consultar con un vendedor 👩',
		'❓ Se você tiver alguma dúvida sobre este produto. Você pode falar com nosso consultor 👩',
		'❓ Si vous avez des questions sur ce produit. Vous pouvez en parler à notre consultant 👩',
		'❓ إذا كانت لديك اي استفسارت حول منتجاتنا، يمكنك التواصل معنا الأن 👩'
	];
	const LNG0063 = [
		'LNG0063',
		1,
		'👆 TALK TO HUMAN 💬',
		'👆 NÓI VỚI NGƯỜI 💬',
		'Чат с человеком 💬',
		'👆 კონსულტანტთან საუბარი 💬',
		'👆 PARLA CON UN UMANO 💬',
		'👆 COMUNICARME CON UN VENDEDOR 💬',
		'👆 FALAR COM HUMANO 💬',
		'👆 PARLER À UN HUMAIN 💬',
		'👆 التحدث لموظف 💬'
	];
	const LNG0064 = [
		'LNG0064',
		1,
		'cash on delivery 💸',
		'thanh toán khi giao hàng 💸',
		'наличные при доставке 💸',
		'ნაღდი ანგარიშსწორებით 💸',
		'pagamento contanti alla consegna 💸',
		'contra reembolso 💸',
		'dinheiro na entrega 💸',
		'paiement à la livraison 💸',
		'الدفع عند التوصيل 💸'
	];

	const LNG0171 = [
		'LNG0171',
		1,
		'💵 Payment',
		'💵 Thanh toán',
		'💵 Оплата',
		'💵 გადახდა',
		'💵 Pagamento',
		'💵 Pago',
		'💵 Pagamento',
		'💵 Paiement',
		'💵 الدفع'
	];

	const LNG0172 = [
		'LNG0172',
		1,
		'Send us the 📋 receipt, once the payment is made',
		'Gửi cho chúng tôi biên lai 📋, sau khi thanh toán được thực hiện',
		'Отправьте нам квитанцию 📋 после оплаты',
		'გამოგვიგზავნეთ 📋 ქვითარი გადახდის შემდეგ',
		'Inviaci la 📋 ricevuta, una volta effettuato il pagamento',
		'Una vez hecho el pago, adjunte el 📋 comprobante',
		'Envie-nos o 📋 recibo, assim que o pagamento for efetuado',
		'Envoyez-nous le reçu 📋, une fois le paiement effectué',
		'ارسل لنا 📋 الفاتورة, عندما تتم عملية الدفع'
	];

	const LNG0173 = [
		'LNG0173',
		1,
		'Make the payment using the following data:',
		'Thực hiện thanh toán bằng cách sử dụng dữ liệu sau:',
		'Произведите оплату, используя следующие данные:',
		'განახორციელეთ გადახდა შემდეგი მონაცემების გამოყენებით:',
		'Effettua il pagamento utilizzando i seguenti dati:',
		'Realiza el pago utilizando los siguientes datos:',
		'Efetue o pagamento utilizando os seguintes dados:',
		'Effectuez le paiement en utilisant les données suivantes:',
		'إتمام الدفعة بإستخدام هذه المعلومات:'
	];

	const LNG0179 = [
		'LNG0179',
		1,
		'Change payment method',
		'Thay đổi phương pháp thanh toán',
		'Изменить способ оплаты',
		'შეცვალეთ გადახდის მეთოდი',
		'Cambia metodo di pagamento',
		'Cambiar método de pago',
		'Mudar o metodo de pagamento',
		'Changer la méthode de paiement',
		'إختار طريقة الدفع'
	];

	const LNG0180 = [
		'LNG0180',
		1,
		'Payment method for order #%s1 successfully changed',
		'Đã thay đổi thành công phương thức thanh toán cho đơn đặt hàng #%s1',
		'Способ оплаты для заказа #%s1 успешно изменен',
		'#%s1 შეკვეთის გადახდის მეთოდი წარმატებით შეიცვალა',
		'Metodo di pagamento per l\'ordine #%s1 modificato correttamente',
		'El método de pago del pedido #%s1 se modificó correctamente',
		'A forma de pagamento do pedido #%s1 foi alterada com sucesso',
		'Le mode de paiement pour la commande #%s1 a été modifié avec succès',
		'تم تغيير طريقة الدفع إلى #%s1 بنجاح'
	];

	const LNG0182 = [
		'LNG0182',
		1,
		"📢📢📢\n\n" . 'Order #%s1 is successfully paid!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . 'Đơn đặt hàng #%s1 đã được thanh toán thành công!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . 'Заказ #%s1 успешно оплачен!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . 'შეკვეთა #%s1 წარმატებით გადახდილია!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . 'L\'ordine #%s1 è stato pagato con successo!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . '¡El pedido #%s1 se pagó con éxito!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . 'O pedido #%s1 foi pago com sucesso!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . 'La commande #%s1 a été payée avec succès!' . "\n\n📢📢📢",
		"📢📢📢\n\n" . 'تم دفع الطلب #%s1 بنجاح!' . "\n\n📢📢📢"
	];

	const LNG0185 = [
		'LNG0185',
		1,
		'payment is successful 👍',
		'thanh toán thành công 👍',
		'платеж прошел успешно 👍',
		'გადახდა წარმატებით დასრულდა 👍',
		'il pagamento è andato a buon fine 👍',
		'el pago es exitoso 👍',
		'pagamento efetuado com sucesso 👍',
		'le paiement est réussi 👍',
		'الدفعة نجحت👍'
	];

	const LNG0183 = [
		'LNG0183',
		1,
		'🌍 Pay with %s1',
		'🌍 Thanh toán bằng %s1',
		'🌍 Оплатить через %s1',
		'🌍 გადაიხადეთ %s1-ით',
		'🌍 Pagare con %s1',
		'🌍 Pagar con %s1',
		'🌍 Pagar com %s1',
		'🌍 Payez avec %s1',
		'🌍 الدفع من خلال %s1'
	];

	const LNG0065 = [
		'LNG0065',
		1,
		'Available sizes:',
		'Kích thước có sẵn:',
		'Размеры:',
		'ხელმისაწვდომი ზომები:',
		'Taglie disponibili:',
		'Talles disponibles:',
		'Tamanhos disponíveis:',
		'Tailles disponibles:',
		'الأحجام المتاحة:'
	];
	const LNG0066 = [
		'LNG0066',
		0,
		'one size',
		'một cỡ',
		'единый размер',
		'',
		''
	];
	const LNG00067 = [
		'LNG0067',
		0,
		'Both you and your 👏 friend %s1 are getting additional ①⓪ points for the weekly raffles!',
		'Cả bạn và bạn 👏 của bạn %s1 đều nhận được thêm 10 điểm cho xổ số hàng tuần',
		'Вы и Ваш 👏 друг %s1 получаете дополнительные ①⓪ очков в еженедельном розыгрыше!',
	];
	const LNG0068 = [
		'LNG0068',
		0,
		'💰 You have %s1 points',
		'💰 Bạn có %s1 điểm',
		'💰 У Вас %s1 очков',
		'',
		''
	];
	const LNG0069 = [
		'LNG0069',
		0,
		'You have invited these friends:',
		'Bạn đã mời những người bạn này:',
		'Друзья, которых Вы пригласили:',
	];
	const LNG0070 = [
		'LNG0070',
		0,
		'This friend has invited you:',
		'Bạn bè đã mời bạn:',
		'Друг, который пригласил Вас:',
		''
	];
	const LNG0071 = [
		'LNG0071',
		1,
		'📲 SWIPE 📲',
		'',
		'📲 СВАЙП 📲',
		'📲 გადასქროლე 📲',
		'📲 SWIPE 📲',
		'📲 Deslice el carrusel 📲',
		'📲 DESLIZE 📲',
		'📲 FAITES DÉFILER📲',
		'📲 اسحب 📲'
	];
	const LNG0072 = [
		'LNG0072',
		1,
		'NEXT PAGE',
		'TRANG TIẾP THEO',
		'СЛЕДУЮЩАЯ СТРАНИЦА',
		'ᲨᲔᲛᲓᲔᲒᲘ ᲒᲕᲔᲠᲓᲘ',
		'PAGINA SUCCESSIVA',
		'SIGUIENTE PÁGINA',
		'PRÓXIMA PÁGINA',
		'PAGE SUIVANTE',
		'الصفحة التالية'
	];
	const LNG0073 = [
		'LNG0073',
		1,
		'📲 CHOOSE CATEGORY:',
		'📲 CHỌN DANH MỤC:',
		'📲 ВЫБЕРИТЕ КАТЕГОРИЮ:',
		'📲 აირჩიე კატეგორია:',
		'📲 SCEGLI LA CATEGORIA:',
		'📲 ELIGE UNA CATEGORÍA:',
		'📲 ESCOLHA A CATEGORIA:',
		'📲 CHOISIR LA CATÉGORIE:',
		'📲 اختار القسم:'
	];
	const LNG0074 = [
		'LNG0074',
		1,
		'📲 CHOOSE PRODUCT:',
		'📲 CHỌN SẢN PHẨM:',
		'📲 ВЫБЕРИТЕ ТОВАР:',
		'📲 აირჩიე პროდუქტი:',
		'📲 SCEGLI IL PRODOTTO:',
		'📲 ELIGE UN PRODUCTO:',
		'📲 ESCOLHA O PRODUTO:',
		'📲 CHOISIR LE PRODUIT:',
		'📲 اختار المنتج:'
	];
	const LNG0075 = [
		'LNG0075',
		1,
		'👆 OPEN NEXT PAGE',
		'👆 MỞ TRANG TIẾP THEO:',
		'👆 ОТКРЫТЬ СЛЕДУЮЩУЮ СТРАНИЦУ',
		'👆 გახსენით შემდეგი გვერდი',
		'👆 PAGINA SUCCESSIVA',
		'👆 IR A LA SIGUIENTE PÁGINA',
		'👆 ABRIR A PRÓXIMA PÁGINA',
		'👆 OUVRIR LA PAGE SUIVANTE',
		'👆 افتح الصفحة التالية'
	];
	const LNG0076 = [
		'LNG0076',
		1,
		'%s1 product(s) left on next pages',
		'%s1 sản phẩm còn lại bên trang tiếp theo',
		'Товаров на следуюших страницах: %s1',
		'დარჩა %s1 პროდუქტი შემდეგ გვერდებზე',
		'Prodotti rimasti nelle prossime pagine: %s1',
		'%s1 producto(s) restantes en la siguientes páginas',
		'%s1 produto(s) restantes nas próximas páginas',
		'%s1 produit(s) restant sur les pages suivantes',
		'%s1 منتج/ات متبقية في الصفحات التالية'
	];
	const LNG0077 = [
		'LNG0077',
		1,
		'Your session has expired ⏳. Please choose product again ;)',
		'Phiên bản của bạn đã hết hạn ⏳. Vui lòng chọn lại sản phẩm ;)',
		'Ваша сессия истекла ⏳. Пожалуйста, выберите товар еще раз ;)',
		'თქვენი სესიის ვადა ამოიწურა ⏳. გთხოვთ ისევ აირჩიოთ პროდუქტი ;)',
		'La tua sessione è scaduta ⏳. Si prega di scegliere di nuovo il prodotto ;)',
		'El tiempo ha expirado ⏳ por favor, elija otra vez ;)',
		'Sua sessão expirou ⏳. Por favor, escolha o produto novamente ;)',
		'Votre session a expiré ⏳. Veuillez choisir à nouveau le produit ;)',
		'تم انهاء جلستك ⏳الرجاء اختيار المنتج مرة أخرى;)'
	];
	const LNG0078 = [
		'LNG0078',
		1,
		'🔥FREE!🔥',
		'🔥FREE!🔥',
		'🔥БЕСПЛАТНО!🔥',
		'',
		'🔥GRATIS!🔥',
		'🔥GRATIS!🔥',
		'🔥GRATUITO!🔥',
		'🔥GRATUIT!🔥',
		'🔥مجاناً!🔥'
	];
	const LNG0079 = [
		'LNG0079',
		1,
		'🔥PROMO! FREE SHIPPING!🔥',
		'🔥PROMO! FREE SHIPPING!🔥',
		'🔥ПРОМО! БЕСПЛАТНАЯ ДОСТАВКА!🔥',
		'🔥 შეთავაზება! უფასო მიწოდება!',
		'🔥PROMO! SPEDIZIONE GRATUITA!🔥',
		'🔥PROMO! ENVÍO GRATIS!🔥',
		'🔥PROMO! FRETE GRÁTIS! 🔥',
		'🔥PROMO! LIVRAISON GRATUITE!🔥',
		'🔥كوبون! توصيل مجاني!🔥'
	];
	const LNG0080 = [
		'LNG0080',
		1,
		'🤗 Name:',
		'🤗 Tên:',
		'🤗 Имя:',
		'🤗 სახელი:',
		'🤗 Nome:',
		'🤗 Nombre:',
		'🤗 Nome:',
		'🤗 Prénom:',
		'🤗 الإسم:'
	];
	const LNG0081 = [
		'LNG0081',
		1,
		'%s1 category(s) left on next pages',
		'%s1 danh mục còn lại ở các trang tiếp theo',
		'Категорий на следуюших страницах: %s1',
		'დარჩა %s1 კატეგორია შემდეგ გვერდებზე',
		'Categorie rimaste nelle prossime pagine: %s1',
		'%s1 categoría(s) restantes en las siguientes páginas',
		'%s1 categoria(s) restantes nas próximas páginas',
		'%s1 catégorie(s) laissée(s) sur les pages suivantes',
		'%s1 قسم متبقي في الصفحة التالية'
	];
	const LNG0082 = [
		'LNG0082',
		1,
		'%s1 product(s)',
		'%s1 sản phẩm',
		'Товаров: %s1',
		'%s1 პროდუქტი',
		'Prodotti: %s1',
		'%s1 producto(s)',
		'%s1 produto(s)',
		'%s1 produit(s)',
//		'%s1 منتج/ات'
		'%s1 منتج/ا'
	];
	const LNG0083 = [
		'LNG0083',
		1,
		'❤ You may also like these products:',
		'❤ Bạn cũng có thể thích các sản phẩm này:',
		'❤ Вам могут понравиться эти товары:',
		'❤ თქვენ ასევე შეიძლება მოგეწონოთ ეს პროდუქტები:',
		'❤ Ti potrebbero piacere anche questi prodotti:',
		'❤ Tal vez te gusten estos productos también:',
		'❤ Você também pode gostar destes produtos:',
		'❤ Vous pourriez aussi aimer ces produits:',
		'❤ منتجات قد تعجبك أيضاً'
	];
	const LNG0084 = [
		'LNG0084',
		1,
		'👆 CHECKOUT ❤',
		'👆 ĐẶT HÀNG TỪ GIỎ ❤',
		'👆 ОФОРМИТЬ ЗАКАЗ ❤',
		'👆შეკვეთის გაგრძელება',
		'👆 CHECKOUT ❤',
		'👆 CHECKOUT ❤',
		'👆 FINALIZAR ❤',
		'👆 PAIEMENT ❤',
		'👆 إتمام الطلب ❤'
	];
	const LNG0085 = [
		'LNG0085',
		1,
		'MY CART 🛒',
		'GIỎ HÀNG CỦA TÔI 🛒',
		'КОРЗИНА 🛒',
		'🛒 ჩემი კალათა',
		'IL MIO CARRELLO 🛒',
		'MI CARRITO DE COMPRA 🛒',
		'MEU CARRINHO 🛒',
		'MON PANIER 🛒',
		'سلتي 🛒'
	];
	const LNG0086 = [
		'LNG0086',
		1,
		'🛒 OPEN CART',
		'🛒 MỞ GIỎ HÀNG',
		'🛒 ОТКРЫТЬ КОРЗИНУ',
		'🛒 კალათის ნახვა',
		'🛒 APRI IL CARRELLO',
		'🛒 CARRITO ABIERTO',
		'🛒 ABRIR CARRINHO',
		'🛒 OUVRIR LE PANIER',
		'🛒 إفتح السلة'
	];
	const LNG0087 = [
		'LNG0087',
		1,
		'👆 CHANGE QUANTITY',
		'👆 THAY ĐỔI SỐ LƯỢNG',
		'👆 ИЗМЕНИТЬ КОЛИЧЕСТВО',
		'👆 რაოდენობის შეცვლა',
		'👆 CAMBIA QUANTITÀ',
		'👆 CAMBIAR CANTIDAD',
		'👆 MUDE A QUANTIDADE',
		'👆 CHANGER LA QUANTITÉ',
		'👆 تغيير العدد'
	];
	const LNG0088 = [
		'LNG0088',
		1,
		'You have %s1 item(s) in your cart',
		'Bạn có %s1 sản phẩm trong giỏ',
		'Товаров в корзине: %s1',
		'კალათაში გაქვთ %s1 პროდუქტი',
		'Articoli nel carrello: %s1',
		'Tienes %s1 producto(s) en el carrito',
		'Você tem %s1 item(ns) em seu carrinho',
		'Vous avez %s1 article(s) dans votre panier',
		'لديك %s1 منتج/ات في سلتك'
	];
	const LNG0089 = [
		'LNG0089',
		1,
		'Do you really want to clear your cart?',
		'Bạn có thật sự muốn xoá giỏ hàng của bạn?',
		'Вы действительно хотите очистить корзину?',
		'ნამდვილად გსურთ თქვენი კალათის გასუფთავება?',
		'Vuoi davvero svuotare il tuo carrello?',
		'¿Deseas vaciar tu carrito de compra?',
		'Você realmente quer limpar seu carrinho?',
		'Vous voulez vraiment vider votre panier?',
		'هل تريد ازالة المنتجات من سلتك ؟'
	];
	const LNG0090 = [
		'LNG0090',
		1,
		'❌ DELETE ITEM',
		'❌ XOÁ SẢN PHẨM',
		'❌ УДАЛИТЬ ТОВАР',
		'❌ კალათიდან ამოშლა',
		'❌ CANCELLA LA VOCE',
		'❌ ELIMINAR PRODUCTO',
		'❌ APAGAR ITEM',
		'❌ SUPPRIMER LE PRODUIT',
		'❌ حذف المنتج'
	];
	const LNG0091 = [
		'LNG0091',
		1,
		'❌ CLEAR CART',
		'❌ DỌN SẠCH GIỎ',
		'❌ ОЧИСТИТЬ КОРЗИНУ',
		'❌ კალათის გასუფთავება',
		'❌ CANCELLA IL CARRELLO',
		'❌ VACIAR CARRITO',
		'❌ LIMPAR CARRINHO',
		'❌ VIDER LE PANIER',
		'❌ تنظيف السلة'
	];
	const LNG0092 = [
		'LNG0092',
		0,
		'You get %s1% DISCOUNT because your cart total is more than %s2',
		'Bạn được GIẢM GIÁ %s1% vì tổng số trong giỏ hàng của bạn nhiều hơn %s2',
	];
	const LNG0093 = [
		'LNG0093',
		0,
		'To get %s1% DISCOUNT buy more than %s2',
		'Để được GIẢM GIÁ% s1%, hãy mua nhiều hơn %s2',
		'Для СКИДКИ в %s1% закажите более чем на %s2',
		'',
		'',
		'',
		'Para obter %s1% de DESCONTO compre mais de %s2',
		'Pour bénéficier de %s1% DE REMISE, achetez plus de %s2'
	];
	const LNG0094 = [
		'LNG0094',
		1,
		'% for order amount',
		'% cho số lượng đặt hàng',
		'% за сумму заказа',
		'% შეკვეთის ოდენობისთვის',
		'% per l\'importo dell\'ordine',
		'% menos en el total',
		'% para o valor do pedido',
		'% pour le montant de la commande',
		'% لمجموع الطلب'
	];

	const LNG0095 = [
		'LNG0095',
		1,
		'% for quantity in order',
		'% cho số lượng trong đơn hàng',
		'% за количество в заказе',
		'% რაოდენობის მიხედვით',
		'% per quantità nell\'ordine',
		'% por ordenar por cantidad',
		'% para quantidade no pedido',
		'% pour la quantité dans la commande',
		'% لعدد المنتجات في الطلب'
	];

	const LNG0096 = [
		'LNG0096',
		1,
		'ℹ Quantity of "%s1" has been changed to %s2',
		'ℹ Số lượng "%s1" đã được thay đổi thành %s2',
		'ℹ Количество товара "%s1" было изменено на %s2',
		'ℹ „%s1“-ის რაოდენობა შეიცვალა %s2-ით',
		'ℹ La quantità di "%s1" è stata cambiata in %s2',
		'ℹ La cantidad de "%s1" se ha cambiado a %s2',
		'ℹ A quantidade de "%s1" foi alterada para %s2',
		'ℹ La quantité de "%s1" a été changée en %s2',
		'ℹ تم تغيير الكمية من "%s1" إلى%s2'
	];
	const LNG0097 = [
		'LNG0097',
		1,
		'ℹ Product "%s1" was deleted from cart',
		'ℹ Sản phẩm "%s1" đã bị xóa khỏi giỏ hàng',
		'ℹ Товар "%s1" был удален из корзины',
		'ℹ პროდუქტი „%s1“ წაიშალა კალათიდან',
		'ℹ Il prodotto "%s1" è stato cancellato dal carrello',
		'ℹ El producto "%s1" fue eliminado del carrito',
		'ℹ O produto "%s1" foi excluído do carrinho',
		'ℹ Le produit "%s1" a été supprimé du panier.',
		'ℹ المنتج "%s1" تم حذفه من السلة بنجاح'
	];
	const LNG0098 = [
		'LNG0098',
		1,
		'ℹ Not enough stock for product "%s1". It was deleted from cart',
		'ℹ Không đủ hàng cho sản phẩm "%s1". Nó đã bị xóa khỏi giỏ hàng',
		'ℹ Товар "%s1" закончился. Он был удален из корзины',
		'ℹ არ არის საკმარისი მარაგი პროდუქტისთვის "%s1". წაშლილია კალათიდან',
		'ℹ Non ci sono abbastanza scorte per il prodotto "%s1". È stato cancellato dal carrello',
		'ℹ No hay suficiente stock para el producto "%s1". Se eliminó del carrito',
		'ℹ Estoque insuficiente para o produto "%s1". Foi deletado do carrinho',
		'ℹ Le stock est insuffisant pour le produit "%s1". Il a été supprimé du panier',
		'ℹ لا يوجد كمية كافية في المخزون من المنتج "%s1". تم حذف المنتج من السلة'
	];
	const LNG0099 = [
		'LNG0099',
		1,
		'ℹ Not enough stock for product "%s1". Quantity was reduced to %s2',
		'ℹ Không đủ hàng cho sản phẩm "%s1". Số lượng đã giảm xuống %s2',
		'ℹ Не хватает товара "%s1". Количество в корзине уменьшено до %s2',
		'ℹ არ არის საკმარისი მარაგი პროდუქტისთვის "%s1". რაოდენობა შემცირდა %s2-მდე',
		'ℹ Non ci sono abbastanza scorte per il prodotto "%s1". La quantità è stata ridotta a %s2',
		'ℹ No hay suficiente stock para el producto "%s1". La cantidad se redujo a %s2',
		'ℹ Estoque insuficiente para o produto "%s1". A quantidade foi reduzida para %s2',
		'ℹ Le stock est insuffisant pour le produit "%s1". La quantité a été réduite à %s2',
		'ℹ لا يوجد كمية كافية في المخزون "%s1". تم تعيين الكمية إلى %s2',
	];
	const LNG0100 = [
		'LNG0100',
		1,
		'ℹ Not enough stock for product "%s1". Cannot add to cart',
		'ℹ Không đủ hàng cho sản phẩm "%s1". Không thể thêm vào giỏ hàng',
		'ℹ Не хватает товара "%s1". Товар не может быть добавлен в корзину',
		'ℹ არ არის საკმარისი მარაგი პროდუქტისთვის "%s1". კალათაში დამატება შეუძლებელია',
		'ℹ Non ci sono abbastanza scorte per il prodotto "%s1". Impossibile aggiungere al carrello',
		'ℹ No hay suficiente stock para el producto "%s1". No se puede agregar al carrito',
		'ℹ Estoque insuficiente para o produto "%s1". Não é possível adicionar ao carrinho',
		'ℹ Pas assez de stock pour le produit "%s1". Impossible d\'ajouter au panier',
		'ℹ لا يوجد كمية كافية من المخزون "%s1". لا يمكن إضافته للسلة'
	];
	const LNG0101 = [
		'LNG0101',
		1,
		'BUY %s1 - GET %s2 OFF',
		'MUA %s1 - ĐƯỢC GIẢM %s2',
		'от %s1 - СКИДКА %s2',
		'იყიდე %s1 - მიიღე %s2 ფასდაკლება',
		'COMPRA %s1 - OTTIENI IL %s2 DI SCONTO',
		'COMRPA %s1 Y OBTEN UN %s2 OFF',
		'COMPRE %s1 - GANHE %s2 DE DESCONTO',
		'ACHETEZ %s1 - OBTENEZ %s2 DE REMISE',
		'إشتري %s1 - وإحصل على %s2 خصم'
	];
	const LNG0102 = [
		'LNG0102',
		1,
		'MY ORDER 📦',
		'ĐƠN HÀNG CỦA TÔI 📦',
		'МОЙ ЗАКАЗ 📦',
		'ჩემი შეკვეთა 📦',
		'IL MIO ORDINE 📦',
		'MI PEDIDO 📦',
		'MINHA ENCOMENDA 📦',
		'MA COMMANDE 📦',
		'طلبي 📦'
	];
	const LNG0103 = [
		'LNG0103',
		1,
		'ORDER 📦 #%s1 is:',
		'ĐƠN HÀNG 📦 #%s1 là:',
		'ЗАКАЗ 📦 #%s1:',
		'შეკვეთა 📦 #%s1 არის:',
		'ORDINE 📦 #%s1 è:',
		'EL PEDIDO 📦 #%s1:',
		'ENCOMENDA 📦 #%s1 é:',
		'COMMANDE 📦 #%s1 est:',
		'طلبي 📦 #%s1 هو:'
	];

	const LNG0184 = [
		'LNG0184',
		1,
		'Order #%s1',
		'Đơn đặt hàng #%s1',
		'Заказ #%s1',
		'შეკვეთა #%s1',
		'Ordine #%s1',
		'Orden #%s1',
		'Pedido #%s1',
		'Commande #%s1',
		'الطلب #%s1'
	];

	const LNG0104 = [
		'LNG0104',
		1,
		'You have %s1 item(s) in your order',
		'Bạn có %s1 sản phẩm trong đơn hàng',
		'Товаров в заказе: %s1',
		'შეკვეთაში გაქვთ %s1 ნივთი',
		'Articoli nel tuo ordine: %s1',
		'Tienes %s1 producto(s) en tu pedido',
		'Você tem %s1 item(ns) em seu pedido',
		'Vous avez %s1 article(s) dans votre commande',
		'لديك %s1 منتج/ات في طلبك'
	];
	const LNG0105 = [
		'LNG0105',
		1,
		'👆 SHOW 📹 VIDEO',
		'HIỂN THỊ 📹 VIDEO',
		'👆 ОТКРЫТЬ 📹 ВИДЕО',
		'📹 ვიდეოს ნახვა',
		'👆 MOSTRA 📹 VIDEO',
		'👆 VER VIDEO 📹',
		'👆 MOSTRAR 📹 VÍDEO',
		'👆 MONTRER 📹 VIDÉO',
		'👆 مشاهدة 📹 فيديو'
	];
	const LNG0106 = [
		'LNG0106',
		1,
		'⬇ CLICK BUTTON TO WATCH ⬇',
		'⬇ NHẤN VÀO NÚT ĐỂ XEM ⬇',
		'⬇ ЧТОБЫ ПОСМОТРЕТЬ НАЖМИТЕ НА КНОПКУ ⬇',
		'⬇ დააწკაპუნეთ ღილაკზე სანახავად ⬇',
		'⬇ CLICCA SUL PULSANTE PER GUARDARE ⬇',
		'⬇ TOCA EL BOTON PARA VER ⬇',
		'⬇ CLIQUE NO BOTÃO PARA ASSISTIR ⬇',
		'⬇ CLIQUEZ SUR LE BOUTON POUR REGARDER ⬇',
		'⬇ إضغط الزر للمشاهدة ⬇'
	];
	const LNG0107 = [
		'LNG0107',
		1,
		'Your coupon will EXPIRE in %s1!',
		'Phiếu giảm giá của bạn sẽ HẾT HẠN sau %s1!',
		'Действие Вашего купона ИСТЕКАЕТ через %s1',
		'თქვენს კუპონს ვადა ეწურება %s1 დღეში',
		'Il tuo coupon scadrà tra %s1 giorni',
		'Tu cupón vencerá en %s1 dias',
		'Seu cupom VAI EXPIRAR em %s1!',
		'Votre coupon expirera dans %s1!',
		'كوبون الخصم الخاص بك ينتهي في %s1!'
	];
	const LNG0108 = [
		'LNG0108',
		1,
		'Your coupon will EXPIRE in 1 day!',
		'Phiếu giảm giá của bạn sẽ HẾT HẠN sau 1 ngày!',
		'Действие Вашего купона ИСТЕКАЕТ завтра!',
		'თქვენი კუპონის ვადა ხვალ იწურება!',
		'Il tuo coupon scade domani!',
		'Tu cupón vencerá mañana!',
		'Seu cupom expirará em 1 dia!',
		'Votre coupon sera EXPIRE dans 1 jour!',
		'كوبون الخصم الخاص بك ينتهي خلال يوم واحد!'
	];
	const LNG0109 = [
		'LNG0109',
		1,
		'Your coupon is EXPIRING today!',
		'Phiếu giảm giá của bạn HẾT HẠN hôm nay!',
		'Действие Вашего купона сегодня',
		'თქვენი კუპონის ვადა დღეს იწურება!',
		'Il tuo coupon scade oggi!',
		'Tu cupón vencerá hoy!',
		'Seu cupom expira hoje!',
		'Votre coupon expire aujourd\'hui!',
		'كوبون الخصم الخاص بك ينتهي اليوم!'
	];
	const LNG0110 = [
		'LNG0110',
		1,
		'Use it now to get %s1% OFF',
		'Sử dụng ngay bây giờ để được GIẢM GIÁ %s1%',
		'Используйте его сейчас, чтобы получить СКИДКУ %s1%',
		'გამოიყენეთ ახლავე, რომ მიიღოთ %s1% ფასდაკლება',
		'Usalo ora per ottenere il %s1% di sconto',
		'Usa este cupón ahora para obtener un %s1%',
		'Use-o agora para obter %s1% OFF',
		'Utilisez-le maintenant pour obtenir une REMISE DE %s1%',
		'إستخدمه الأن للحصول على %s1% خصم'
	];
	const LNG0112 = [
		'LNG0112',
		1,
		'Bot 🤖%s1 recommends you these products 🤩',
		'Bot 🤖%s1 giới thiệu cho bạn những sản phẩm này 🤩',
		'Бот 🤖%s1 рекомендует Вам эти товары 🤩',
		'ბოტი 🤖 გირჩევთ ამ პროდუქტებს 🤩',
		'Bot 🤖%s1 ti raccomanda questi prodotti 🤩',
		'Te recomendamos este producto 🤩',
		'O assistente virtual 🤖%s1 recomenda-te estes produtos 🤩',
		'Bot 🤖%s1 vous recommande ces produits 🤩',
		'نحن 🤖%s1 ننصحك بهذه المنتجات أيضاً 🤩'
	];
	const LNG0113 = [
		'LNG0113',
		1,
		'📲 CHOOSE BRAND:',
		'📲 CHỌN THƯƠNG HIỆU:',
		'📲 ВЫБЕРИТЕ БРЕНД:',
		'📲 აირჩიე ბრენდი:',
		'📲 SCEGLI LA MARCA:',
		'📲 ELIJA UNA MARCA',
		'📲 ESCOLHA A MARCA:',
		'📲 CHOISIR LA MARQUE :',
		'📲 إختار العلامة التجارية:'
	];
	const LNG0114 = [
		'LNG0114',
		1,
		'You order was confirmed! 🚀',
		'Đơn hàng của bạn đã được xác nhận! 🚀',
		'Ваш заказ был подтвержден! 🚀',
		'თქვენი შეკვეთა დადასტურდა! 🚀',
		'Il tuo ordine è stato confermato! 🚀',
		'Tu orden fue confirmada 🚀',
		'Seu pedido foi confirmado! 🚀',
		'Votre commande a été confirmée ! 🚀',
		'تم تأكيد طلبك بنجاح! 🚀'
	];
	const LNG0115 = [
		'LNG0115',
		1,
		'👉 Estimated dates of delivery 💌 are from %s1 to %s2',
		'👉 Ngày giao hàng ước tính 💌 là từ %s1 đến %s2',
		'👉 Примерные даты доставки 💌 от %s1 до %s2',
		'👉 მიწოდების სავარაუდო თარიღები 💌 არის %s1 - %s2',
		'👉 Le date di consegna stimate 💌 sono dal %s1 al %s2',
		'👉 La fecha de entrega estimada es entre el %s1 a %s2',
		'👉 As datas estimadas de entrega 💌 são de %s1 a %s2',
		'👉 Les dates de livraison estimées 💌 sont de %s1 à %s2',
		'👈 الوقت المتوقع للتوصيل 💌 هو %s1 حتى %s2'
	];
	const LNG0116 = [
		'LNG0116',
		1,
		'👆 MORE..',
		'👆 THÊM..',
		'👆 ЕЩЁ..',
		'👆 მეტი..',
		'👆 PIÙ...',
		'👆 MÁS...',
		'👆 MAIS..',
		'👆 PLUS...',
		'👆 المزيد..'
	];
	const LNG0117 = [
		'LNG0117',
		0,
		'😎 Check out these products from our fellow bot 🤖 %s1',
		'😎 Xem các sản phẩm này từ bot của chúng tôi 🤖 %s1',
		'😎 А вот и товары от нашего друга-бота 🤖 %s1',
	];
	const LNG0119 = [
		'LNG0119',
		0,
	];

	const LNG0128 = [
		'LNG0128',
		0,
		[
			'day',
			'days'
		],
		'ngày',
		[
			'день',
			'дня',
			'дней'
		],
		'დღე',
		[
			'giorno',
			'giorni'
		],
		[
			'día',
			'dias'
		],
		[
			'dia',
			'dias'
		],
	];
	const LNG0129 = [
		'LNG0129',
		0,
		'Please wait about 🕓 10 seconds. I am 🤖 processing your image...',
		'Vui lòng đợi khoảng 🕓 10 giây. Tôi đang🤖 xử lý hình ảnh của bạn ...',
		"Мне нужно около 🕓 10 секунд, чтобы обработать 🤖 изображение\nПожалуйста, подождите..."
	];
	const LNG0130 = [
		'',
		0,
		'🔎 This is what I\'ve found by your image 🖼️:',
		'🔎 Đây là những gì tôi đã tìm thấy qua hình ảnh của bạn 🖼️',
		'🔎 Вот что я нашел по изображению 🖼️:'
	];
	const LNG0131 = [
		'LNG0131',
		1,
		'For your information on how to buy from bot 🤖',
		'Cho thông tin của bạn về cách mua hàng từ bot 🤖',
		'Как купить у бота 🤖:',
		'თქვენი ინფორმაციისთვის, თუ როგორ ვიყიდოთ ბოტიდან 🤖',
		'Per le tue informazioni su come acquistare da bot 🤖',
		'¿Cómo comprar a través del asistente virtual 🤖?',
		'Para sua informação sobre como comprar do bot 🤖',
		'',
		'لمعلوماتك حول طريقة الشراء من خلال المحادثة 🤖'
	];
	const LNG0132 = [
		'LNG0132',
		1,
		'These sizes are actually IN STOCK!',
		'Những kích thước này có sẵn TRONG KHO!',
		'Эти размеры действительно есть в наличии',
		'ეს ზომები რეალურად არის მარაგში!',
		'Queste misure sono effettivamente IN STOCK!',
		'Tenemos estos talles EN STOCK',
		'Esses tamanhos estão realmente EM ESTOQUE!',
		'Ces tailles sont actuellement EN STOCK!',
		'هذه الأحجام متوفرة في المخزون لدينا!'
	];
	const LNG0133 = [
		'LNG0133',
		1,
		'The price is',
		'Giá là',
		'Цена:',
		'ღირებულება:',
		'Il prezzo è',
		'El precio es',
		'O preço é',
		'Le prix est de',
		'السعر هو'
	];
	const LNG0135 = [
		'LNG0135',
		1,
		'The shipping is FREE',
		'Vận chuyển MIỄN PHÍ',
		'Доставка БЕСПЛАТНО',
		'მიწოდება უფასოა',
		'La spedizione è GRATIS',
		'El envío es GRATUITO',
		'O frete é GRÁTIS',
		'La livraison est GRATUITE',
		'الشحن مجاني'
	];
	const LNG0136 = [
		'LNG0136',
		1,
		'COD! We accept Cash On Delivery',
		'COD! Chúng tôi nhận thanh toán khi giao hàng',
		'Мы принимаем наличные при доставке',
		'ჩვენ ვიღებთ ნაღდი ანგარიშსწორებით ადგილზე მიტანის დროს',
		'CONTRASSEGNO! Accettiamo il pagamento alla consegna',
		'Aceptamos pagos contra reembolso',
		'BACALHAU! Aceitamos dinheiro na entrega',
		'Nous acceptons le paiement à la livraison',
		'تهانينا! تم الموافقة على الدفع عند الإستلام'
	];
	const LNG0137 = [
		'LNG0137',
		1,
		'Please don\'t hesitate to make an order - just push BUY button!',
		'Vui lòng không ngần ngại đặt hàng - chỉ cần nhấn nút MUA! ',
		'Чтобы сделать заказ, жмите на кнопку КУПИТЬ!',
		'გთხოვთ, ნუ მოგერიდებათ შეკვეთის გაკეთება - უბრალოდ დააჭირეთ ყიდვის ღილაკს!',
		'Non esitate a fare un ordine - basta premere il pulsante ACQUISTA!',
		'Por favor no adude en hacer su orden. Toque en el boton COMPRAR!',
		'Por favor, não hesite em fazer um pedido - basta pressione o botão COMPRAR!',
		'N\'hésitez pas à passer une commande - il suffit d\'appuyer sur le bouton ACHETER!',
		'رجاءً لا تترد في الشراء، فقط إضغط على زر الشراء'
	];
	const LNG0138 = [
		'LNG0138',
		1,
		'Please choose size range:',
		'Vui lòng chọn phạm vi kích thước:',
		'Выберите диапазон размеров:',
		'გთხოვთ, აირჩიოთ ზომის დიაპაზონი:',
		'Si prega di scegliere la gamma di dimensioni:',
		'Por favor elija un rango de talles:',
		'Por favor, escolha a faixa de tamanho:',
		'Veuillez choisir la gamme de tailles:',
		'الرجاء إختيار الحجم:'
	];
	const LNG0139 = [
		'LNG0139',
		0,
		'Please wait about 🕓 20 seconds. I am 🤖 processing your image...',
		'Vui lòng đợi khoảng 🕓 20 giây. Tôi đang 🤖 xử lý hình ảnh của bạn ...',
		"ℹ️ Мне нужно около 🕓 20 секунд, чтобы обработать 🤖 изображение\nПожалуйста, подождите..."
	];
	const LNG0140 = [
		'LNG0140',
		0,
		'ℹ️ Sorry :) Cannot 🤖 process this image. 🙏 Please try again!',
		'ℹ️ Xin lỗi :) Không thể 🤖 xử lý hình ảnh này. 🙏 Vui lòng thử lại!',
		'ℹ️ Ой! :) Не могу 🤖 обработать это фото. 🙏 Пожалуйста, попробуйте другое!'
	];
	const LNG0141 = [
		'LNG0141',
		0,
		'ℹ️ The image is too small. 🙏 Please send me a bigger one :)',
		'ℹ️ Hình ảnh quá nhỏ. 🙏 Vui lòng gửi hình lớn hơn :)',
		'ℹ️ Фото слишком маленькое. 🙏 Пожалуйста, попробуйте фото большего размера :)'
	];
	const LNG0142 = [
		'LNG0142',
		0,
		'ℹ️ Looks like you are too far away on this photo. 🙏 Please send me a closer photo',
		'ℹ️ Có vẻ như bạn đang ở quá xa trên bức ảnh này. 🙏 Vui lòng gửi cho tôi một bức ảnh gần hơn',
		'ℹ️ Мне кажется 🤖 вы слишком далеко на этом фото. 🙏 Пожалуйста, попробуйте фото поближе'
	];
	const LNG0143 = [
		'LNG0143',
		0,
		'ℹ️ Please look straight into the camera. 🙏 Try again!',
		'ℹ️ Hãy nhìn thẳng vào máy ảnh. 🙏 Thử lại!',
		'ℹ️ Пожалуйста, смотрите прямо в камеру!'
	];
	const LNG0144 = [
		'LNG0144',
		0,
		'👆 Try another photo',
		'👆 Thử ảnh khác',
		'👆 Другое селфи'
	];
	const LNG0145 = [
		'LNG0145',
		0,
		'👆 Try on!',
		'👆 Đội thử!',
		'👆 Примерить!'
	];
	const LNG0146 = [
		'LNG0146',
		0,
		'Please send 🤖 me a 🤩 selfie',
		'Vui lòng 🤖 gửi một 🤩 bức ảnh tự sướng',
		'Пожалуйста, пошлите 🤖 мне 🤩 селфи'
	];
	const LNG0147 = [
		'LNG0147',
		0,
		"\nYou can 🤖 automatically try on 🤠 hats using your photo!",
		"\nBạn có thể 🤖 tự động đội 🤠 mũ bằng cách sử dụng ảnh của bạn!",
		"\n Вы можете 🤖 автоматически примерить 🤠 кепки по фото"
	];
	const LNG0148 = [
		'LNG0148',
		0,
		'👆 Try on hats!',
		'👆 Thử mũ!',
		'👆 Примерить кепки!'
	];
	const LNG0149 = [
		'LNG0149',
		1,
		'This product has only ONE SIZE',
		'Sản phẩm này chỉ có MỘT SIZE',
		'У этого товара только ОДИН РАЗМЕР',
		'ამ პროდუქტს აქვს მხოლოდ ერთი ზომა',
		'Questo prodotto ha solo UNA TAGLIA',
		'Este producto tiene un SOLO TALLE',
		'Este produto tem apenas UM TAMANHO',
		'Ce produit n\'a qu\'UNE SEULE TAILLE',
		'المنتج يتوفر بحجم واحد فقط'
	];
	const LNG0150 = [
		'LNG0150',
		1,
		"Here's the size chart for",
		'Đây là bảng kích thước cho',
		'Таблица размеров для категории',
		'აქ არის ზომის სქემა ამისთვის',
		'Ecco la tabella delle taglie per',
		'Aquí está la tabla de talles de',
		'Aqui está a tabela de tamanhos para',
		'Voici le tableau des tailles pour',
		'هنا مخطط الأحجام لـِ'
	];
	const LNG0151 = [
		'LNG0151',
		1,
		'Size %s1 is IN STOCK',
		'Size %s1 CÒN HÀNG',
		'Размер %s1 В НАЛИЧИИ',
		'ზომა %s1 არის მარაგში',
		'La taglia %s1 è IN STOCK',
		'Tenemos DISPONIBLE el talle %s1',
		'O tamanho %s1 está EM ESTOQUE',
		'La taille %s1 est EN STOCK',
		'الحجم %s1 متوفر في المخزون'
	];
	const LNG0152 = [
		'LNG0152',
		1,
		'Size %s1 is OUT OF STOCK',
		'Size %s1 HẾT HÀNG',
		'Размера %s1 НЕТ В НАЛИЧИИ',
		'ზომა %s1 არ არის მარაგში',
		'La taglia %s1 NON è DISPONIBILE',
		'El talle %s1 NO está DISPONIBLE.',
		'O tamanho %s1 está ESGOTADO',
		'La taille %s1 est en rupture de stock',
		'الحجم %s1 غير متوفر في المخزون'
	];
	const LNG0153 = [
		'LNG0153',
		0,
		"Sorry 🤖! Couldn't 🔎 find anything by your 🖼️ image!\" Please try another one ;)",
		'Xin lỗi 🤖! Không thể🔎 tìm thấy bất cứ gì bằng hình ảnh 🖼️ của bạn!\nVui lòng thử một cái khác;)',
		"Ой 🤖! По вашему 🖼️ изображению ничего не найдено!\n Пожалуйста, попробуйте другое ;)"
	];
	const LNG0154 = [
		'LNG0154',
		1,
		'Size %s1 is not available for this product',
		'Size %s1 không có cho sản phẩm này',
		'Размер %s1 недоступен для этого товара',
		'ზომა %s1 არ არის ხელმისაწვდომი ამ პროდუქტისთვის',
		'La taglia %s1 non è disponibile per questo prodotto',
		'No tenemos disponible talle %s1 para este producto',
		'O tamanho %s1 não está disponível para este produto',
		'La taille %s1 n\'est pas disponible pour ce produit',
		'الحجم %s1 غير متوفر لهذا المنتج'
	];
	const LNG0155 = [
		'LNG0155',
		1,
		'Here are similar products of the same size:',
		'Dưới đây là sản phẩm tương tự cùng kích thước:',
		'Похожие товары того же размера:',
		'აქ არის იგივე ზომის მსგავსი პროდუქტები:',
		'Qui ci sono prodotti simili della stessa dimensione:',
		'Aquí tienes productos similares de ese talle:',
		'Aqui estão produtos semelhantes do mesmo tamanho:',
		'Voici des produits similaires de la même taille:',
		'هنا منتجات مشابهة بنفس الحجم:'
	];
	const LNG0156 = [
		'LNG0156',
		1,
		'Ask in messenger',
		'',
		'',
		'',
		'Chiedi in messenger',
		'Ordenar en messenger',
		'Pergunte no messenger',
		'Demandez dans le messenger',
		'إسأل في المحادثة'
	];
	const LNG0157 = [
		'LNG0157',
		1,
		'BUY %s1 - GET %s2 OFF',
		'MUA %s1 - ĐƯỢC GIẢM %s2',
		'КУПИ %s1 - СКИДКА %s2',
		'შეიძინეთ %s1 აშშ დოლარი - მიიღეთ %s2 ფასდაკლება',
		'ACQUISTA %s1 EUR - OTTIENI IL %s2 DI SCONTO',
		'COMPRA %s1 y recibe un %s2 OFF',
		'COMPRE %s1 - GANHE %s2 DE DESCONTO',
		'ACHETEZ %s1 - OBTENEZ %s2 DE REMISE',
		'إشتري %s1 - واحصل على%s2 خصم'
	];

	const LNG0167 = [
		'LNG0167',
		1,
		"Your name for 🚚 shipping:\n%s1\nDo you want to use this name?",
		"Tên của bạn cho 🚚 vận chuyển:\n%s1\nBạn có muốn sử dụng tên này?",
		"Ваше имя для 🚚 доставки:\n%s1\nИспользовать это имя?",
		"თქვენი სახელი 🚚 ტრანსპორტირებისთვის:\n%s1\nგსურთ გამოიყენოთ ეს სახელი?",
		"Il tuo nome per 🚚 spedizione:\n%s1\nVuoi usare questo nome?",
		"Su nombre para 🚚 envío:\n%s1\n¿Quieres usar este nombre?",
		'Seu nome para 🚚 envio: %s1 Deseja usar este nome?',
		'Votre prénom pour la livraison 🚚 : %s1 Voulez-vous utiliser ce prénom?',
		"إسمك للتوصل🚚:\n%s1\هل تريد إستخدام هذا الإسم ؟"
	];

	const LNG0168 = [
		'LNG0168',
		1,
		'Please input first name:',
		'Vui lòng nhập tên:',
		'Пожалуйста, введите имя:',
		'გთხოვთ შეიყვანოთ სახელი:',
		'Si prega di inserire il nome:',
		'Por favor ingrese el primer nombre:',
		'Por favor, insira o primeiro nome:',
		'Veuillez saisir le prénom:',
		'الرجاء ادخل إسمك الأول'
	];

	const LNG0169 = [
		'LNG0169',
		1,
		'Please input last name:',
		'Vui lòng nhập họ',
		'Пожалуйста, введите фамилию:',
		'გთხოვთ შეიყვანოთ გვარი:',
		'Si prega di inserire il cognome:',
		'Por favor ingrese el apellido:',
		'Por favor, insira o sobrenome:',
		'Veuillez saisir le nom de famille:',
		'الرجاء ادخل إسمك الثاني'
	];

	const LNG0118 = [
		'LNG0118',
		1,
		"Your 🚚 shipping ☎ phone number is %s1\nDo you want to use this phone number?",
		"Số điện thoại ☎️ vận chuyển 🚚 của bạn là %s1\nBạn có muốn sử dụng số điện thoại này?",
		"Ваш ☎ телефон для 🚚 доставки: %s1\nХотите использовать этот номер?",
		"🚚 თქვენი მიწოდების ტელეფონის ნომერია: %s1\nგსურთ ამ ტელეფონის ნომრის გამოყენება?",
		"Il tuo numero di telefono 🚚 di spedizione ☎ è %s1\nVuoi usare questo numero di telefono?",
		"Su 🚚 número de teléfono de envío ☎ es %s1\n¿Te gustaría utilizar este número de teléfono?",
		'Seu 🚚 número de telefone para envio ☎ é %s1 Deseja usar este número de telefone?',
		'Votre 🚚 numéro de téléphone d\'expédition ☎ est %s1 Voulez-vous utiliser ce numéro de téléphone?',
		'رقم هاتفك 🚚 المخزن للتوصيل ☎ %s1 هل تريد إستخدام نفس الرقم؟'
	];

	const LNG0120 = [
		'LNG0120',
		1,
		'Please write ☎ phone number for 🚚 shipping:',
		'Vui lòng viết ☎ số điện thoại cho 🚚 vận chuyển:',
		'Пожалуйста, введите ☎ телефон для связи с курьером при 🚚 доставке',
		'🚚 მიწოდებისთვის გთხოვთ მოგვწეროთ ☎ ტელეფონის ნომერი:',
		'Si prega di scrivere ☎ numero di telefono per 🚚 la spedizione:',
		'Escribenos tú ☎ número de teléfono por favor:',
		'Por favor, escreva ☎ número de telefone para 🚚 envio:',
		'Veuillez écrire ☎ le numéro de téléphone pour 🚚 la livraison:',
		'رجاءً ☎ ادخل رقم هاتفك 🚚 للشحن:'
	];

	const LNG0028 = [
		'LNG0028',
		1,
		'Please enter the full number in international format with the country code including “+”. For example: +15417543010',
		'Vui lòng nhập số đầy đủ ở định dạng quốc tế với mã quốc gia bao gồm “+”. Ví dụ: +84112233455',
		'Пожалуйста, введите полный телефонный номер в международном формате с кодом страны, включая “+”. Например: +79161122334',
		'გთხოვთ, შეიყვანოთ სრული ნომერი საერთაშორისო ფორმატში ქვეყნის კოდით „+“-ის ჩათვლით. მაგალითად: +99517543010',
		'Si prega di inserire il numero completo nel formato internazionale con il codice del paese incluso il “+”. Per esempio: +39123445042',
		'Por favor introduzca el número incluyendo el código del pais. Por ej +59812312312',
		'Insira o número completo com o indicativo do seu país incluindo “+”. Por exemplo: +2389252025',
		'Veuillez saisir le numéro complet au format international avec l\'indicatif du pays incluant "+". Par exemple : +15417543010',
		'رجاءً ادخل رقم هاتفك الكامل مع المقدمة الدولية "+"'
	];

	const LNG0162 = [
		'LNG0162',
		1,
		"Your email is %s1\nCorrect?",
		"Email của bạn là %s1\nĐúng không?",
		"Ваш email: %s1\nВерно?",
		"თქვენი ელფოსტა არის %s1\nეს სწორია?",
		"La tua email è %s1\nÈ corretto?",
		"Tu correo es %s1\n¿Correcto?",
		'Seu e-mail é %s1 Correto?',
		'Votre adresse électronique est %s1 Correct?',
		'هل بريدك الإلكتروني صحيح ؟'
	];

	const LNG0163 = [
		'LNG0163',
		1,
		'Please input email:',
		'Vui lòng nhập email: ',
		'Пожалуйста, введите email:',
		'გთხოვთ შეიყვანოთ ელფოსტა:',
		'Si prega di inserire l\'e-mail:',
		'Por favor ingrese el correo electrónico:',
		'Por favor, insira o e-mail:',
		'Veuillez saisir l\'adresse email:',
		'رجاءً ادخل بريدك الإلكتروني:'
	];

	const LNG0164 = [
		'LNG0164',
		1,
		'Please input correct email:',
		'Vui lòng nhập đúng email:',
		'Пожалуйста, введите корректный email:',
		'გთხოვთ შეიყვანოთ სწორი ელფოსტა:',
		'Si prega di inserire l\'e-mail corretta:',
		'Por favor ingrese el correo electrónico correcto:',
		'Por favor, insira o e-mail correto:',
		'euillez saisir l\'adresse email correcte:',
		'رجاءً إدخل بريد إلكتروني صحيح:'
	];

	const LNG0122 = [
		'LNG0122',
		1,
		"Your 🚚 shipping 🏠 address is %s1\nDo you want to use this address?",
		"Địa chỉ 🏠 vận chuyển 🚚 của bạn là %s1\nBạn có muốn sử dụng địa chỉ này?",
		"Ваш 🏠 адрес 🚚 доставки: %s1\nХотите использовать этот адрес?",
		"🚚 თქვენი მიწოდების მისამართია %s1\nგსურთ ამ მისამართის გამოყენება?",
		"Il tuo 🚚 indirizzo di spedizione 🏠 è %s1\nVuoi usare questo indirizzo?",
		"🏠 Tu dirección es %s1\n¿Te gustaría utilizar esta dirección?",
		'Seu 🚚 endereço de entrega 🏠 é %s1 Deseja usar este endereço?',
		'Votre adresse de livraison 🚚 🏠 est %s1 Voulez-vous utiliser cette adresse?',
		'عنوان🚚 الشحن 🏠 هو %s1 هل تريد إستخدام نفس العنوان ؟'
	];

	const LNG0158 = [
		'LNG0158',
		1,
		'Please input country:',
		'Vui lòng nhập quốc gia:',
		'Пожалуйста, введите страну:',
		'გთხოვთ, შეიყვანოთ ქვეყანა:',
		'Si prega di inserire il paese:',
		'Por favor ingrese el país:',
		'Insira o país:',
		'Veuillez saisir le pays:',
		'ادخل المدينة :'
	];

	const LNG0161 = [
		'LNG0161',
		1,
		'Please input state:',
		'Vui lòng nhập khu vực:',
		'Пожалуйста, введите регион:',
		'გთხოვთ შეიყვანოთ რეგიონი:',
		'Si prega di inserire la regione:',
		'Por favor ingrese la región:',
		'Insira o estado:',
		'Veuillez saisir l\'état:',
		'ادخل المقاطعة :'
	];

	const LNG0159 = [
		'LNG0159',
		1,
		'Please input city:',
		'Vui lòng nhập thành phố:',
		'Пожалуйста, введите город:',
		'გთხოვთ შეიყვანოთ ქალაქი:',
		'Si prega di inserire la città:',
		'Por favor ingrese la ciudad:',
		'Por favor, insira a cidade:',
		'Veuillez saisir la ville:',
		'ادخل المدينة :'
	];
	const LNG0160 = [
		'LNG0160',
		1,
		'Please input postcode:',
		'Vui lòng nhập mã bưu điện:',
		'Пожалуйста, введите почтовый индекс:',
		'გთხოვთ შეიყვანოთ საფოსტო კოდი:',
		'Si prega di inserire il codice postale:',
		'Por favor ingrese el código postal:',
		'Por favor, insira o código postal:',
		'Veuillez saisir le code postal:',
		'ادخل الرمز البريدي :'
	];

	const LNG0124 = [
		'LNG0124',
		1,
		'Please write 🚚 shipping 🏠 address:',
		'Vui lòng viết địa chỉ 🚚:',
		'Пожалуйста, введите 🏠 адрес 🚚 доставки:',
		'გთხოვთ დაწეროთ 🚚 მიწოდება 🏠 მისამართი:',
		'Si prega di scrivere l\'indirizzo 🚚 di spedizione 🏠:',
		'Por favor escribe tu 🏠 dirección:',
		'Por favor, escreva 🚚 endereço de envio 🏠:',
		'Veuillez écrire l\'adresse de la livraison 🚚 🏠:',
		'رجاءً أدخل 🚚 عنوان 🏠 الشحن:'
	];

	const LNG0125 = [
		'LNG0125',
		1,
		'Please enter a correct address:',
		'Vui lòng nhập địa chỉ chính xác:',
		'Пожалуйста, введите корректный 🏠 адрес 🚚 доставки:',
		'გთხოვთ შეიყვანოთ სწორი მისამართი:',
		'Si prega di inserire un indirizzo corretto:',
		'Por favor ingrese una 🏠 dirección correcta:',
		'Insira um endereço correto:',
		'Veuillez entrer une adresse correcte:',
		'رجاءً قم بإدخال عنوان صحيح للشحن:'
	];


	const LNG0126 = [
		'LNG0126',
		1,
		'Please choose payment method:',
		'Vui lòng chọn phương thức thanh toán:',
		'Пожалуйста, выберите способ оплаты:',
		'გთხოვთ, აირჩიოთ გადახდის მეთოდი:',
		'Si prega di scegliere il metodo di pagamento:',
		'¿Por donde desea abonar?',
		'Por favor, escolha a forma de pagamento:',
		'Veuillez choisir le mode de paiement:',
		'رجاءً إختار طريقة الدفع:'
	];

	const LNG0178 = [
		'LNG0178',
		1,
		'press button to make payment 👇',
		'nhấp vào nút để tiếp tục thanh toán 👇',
		'нажмите кнопку чтобы перейти к оплате 👇',
		'დააჭირეთ ღილაკს გადახდის გასაკეთებლად 👇',
		'premere il pulsante per procedere al pagamento 👇',
		'presione el botón para proceder al pago 👇',
		'pressione o botão para prosseguir com o pagamento 👇',
		'appuyez sur le bouton pour effectuer le paiement 👇',
		'إضغط الزر للدفع 👇'
	];

	const LNG0127 = [
		'LNG0127',
		0,
		'Is that correct?',
		'Đúng không?',
		'Верно?',
		'ეს სწორია?',
		'È corretto?',
		'¿Es correcto?',
		'',
		''
	];

	const LNG0166 = [
		'LNG0166',
		1,
		'Change info',
		'Thay đổi thông tin',
		'Изменить информацию',
		'ინფორმაციის შეცვლა',
		'Modifica informazioni',
		'Cambiar información',
		'Alterar informações',
		'Modifier les informations',
		'تعديل المعلومات'
	];

	const LNG0170 = [
		'LNG0170',
		1,
		'Cannot calculate shipping',
		'Không thể tính toán vận chuyển',
		'Доставка не рассчитана',
		'ტრანსპორტირების გამოთვლა შეუძლებელია',
		'Impossibile calcolare la spedizione',
		'No se puede calcular el envío',
		'Não consigo calcular o frete',
		'Impossible de calculer les frais d\'expédition',
		'لا يمكن إحتساب قيمة الشحن'
	];

	const LNG0121 = [
		'LNG0121',
		0,
		"ℹ️ Your session has expired\nWhen you are ready to make an order just press CHECK OUT ❤ button again 😘",
		"ℹ️ Phiên bản của bạn đã hết hạn\nKhi bạn đã sẵn sàng đặt hàng, chỉ cần nhấn nút ĐẶT HÀNG TỪ GIỎ ❤ một lần nữa 😘",
		"ℹ️ Ваша сессия истекла\nНажмите кнопку ОФОРМИТЬ ЗАКАЗ ❤ когда снова будете готовы сделать заказ 😘",
	];

	const LNG0186 = [
		'LNG0186',
		1,
		'return to bot',
		'quay lại bot',
		'вернуться к боту',
		'დაბრუნება ბოტზე',
		'torna al bot',
		'volver al bot',
		'retornar ao bot',
		'retour au robot',
		'العودة'
	];

	const LNG0187 = [
		'LNG0187',
		1,
		'Order is paid',
		'Đơn hàng đã được thanh toán',
		'Заказ оплачен',
		'შეკვეთა გადახდილია',
		'L\'ordine è pagato',
		'El pedido esta pagado',
		'O pedido está pago',
		'La commande est payée',
		'يتم دفع الطلب'

	];

	const LNG0188 = [
		'LNG0188',
		1,
		'Order is not paid',
		'Đơn hàng chưa được thanh toán',
		'Заказ не оплачен',
		'შეკვეთა არ არის გადახდილი',
		'L\'ordine non viene pagato',
		'El pedido no está pagado.',
		'O pedido não foi pago',
		'La commande n\'est pas payée',
		'لا يتم دفع الطلب'
	];

	const LNG0189 = [
		'LNG0189',
		1,
		'💳 Online payment',
		'💳 Thanh toán trực tuyến',
		'💳 Онлайн-оплата',
		'💳 ონლაინ აღწერა',
		'💳 Opera online',
		'💳 Plataforma online',
		'💳 Plataforma Online',
		'💳 Paiement en ligne',
		'💳الطلب عبر الإنترنت'
	];

	const LNG0190 = [//next
		'LNG0190',
		0,
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		'',
		''
	];
}
