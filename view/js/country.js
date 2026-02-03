//<!--  Docs at: http://www.microcosmotalk.com/tech/scripts/
// @license magnet:?xt=urn:btih:e95b018ef3580986a04669f1b5879592219e2a7a&dn=public-domain.txt Public Domain
//
// SPDX-License-Identifer = CC0-1.0
//
// SPDX-FileCopyrightText = Jim Carlock
//
// NOTE:
// This code is placed into the public domain and may be used in any manner desired.
// 
// Jim Carlock obtained the list of country names and state names from an HTML
// document at Microsoft's website.
//
// Thursday, January 13, 2005, 7:00:52 PM
// 
var gLngMaxStateLength = 0;
var gLngMaxCountryLength = 0;
var gLngNumberCountries = 0;
var gLngNumberStates = 0;
var gLngSelectedCountry = 0;
var gLngSelectedState = 0;
var gArCountryInfo;
var gArStateInfo;
var gCountryList = [];
var aStates = new Array();

aStates[""] = ""; // Empty key
aStates["Afghanistan"] = "|Badakhshan|Badghis|Baghlan|Balkh|Bamyan|Daykundi|Farah|Faryab|Ghazni|Ghor|Helmand|Herat|Jowzjan|Kabul|Kandahar|Kapisa|Khost|Kunar|Kunduz|Laghman|Logar|Maidan Wardak|Nangarhar|Nimruz|Nuristan|Paktia|Paktika|Panjshir|Parwan|Samangan|Sar-e Pol|Takhar|Uruzgan|Zabul"; // 34 provinces — https://en.wikipedia.org/wiki/Provinces_of_Afghanistan
aStates["Åland Islands"] = "|Brändö|Eckerö|Finström|Föglö|Geta|Hammarland|Jomala|Kökar|Kumlinge|Lemland|Lumparland|Mariehamn|Saltvik|Sottunga|Sund|Vårdö"; // 16 municipalities — https://sv.wikipedia.org/wiki/%C3%85lands_kommuner https://en.wikipedia.org/wiki/Municipalities_of_%C3%85land
aStates["Albania"] = "|Berat|Dibër|Durrës|Elbasan|Fier|Gjirokastër|Korçë|Kukës|Lezhë|Shkodër|Tiranë|Vlorë"; // 12 counties — https://sq.wikipedia.org/wiki/Qarqet_e_Shqip%C3%ABris%C3%AB https://en.wikipedia.org/wiki/Counties_of_Albania
aStates["Algeria"] = "|Adrar|Aïn Defla|Aïn Témouchent|Algiers|Annaba|Batna|Béchar|Béjaïa|Beni Abbes|Biskra|Blida|Bordj Badji Mokhtar|Bordj Bou Arréridj|Bouira|Boumerdès|Chlef|Constantine|Djanet|Djelfa|El Bayadh|El Meghaier|El Meniaa|El Oued|El Tarf|Ghardaïa|Guelma|Illizi|In Guezzam|In Salah|Jijel|Khenchela|Laghouat|M'Sila|Mascara|Médéa|Mila|Mostaganem|Naâma|Oran|Ouargla|Ouled Djellal|Oum El Bouaghi|Relizane|Saïda|Sétif|Sidi Bel Abbès|Skikda|Souk Ahras|Tamanrasset|Tébessa|Tiaret|Tindouf|Tipaza|Tissemsilt|Tizi Ouzou|Tlemcen|Touggourt"; // 58 provinces — https://ar.wikipedia.org/wiki/%D9%88%D9%84%D8%A7%D9%8A%D8%A7%D8%AA_%D8%A7%D9%84%D8%AC%D8%B2%D8%A7%D8%A6%D8%B1 https://en.wikipedia.org/wiki/Provinces_of_Algeria
aStates["American Samoa"] = "|Eastern|Manu'a|Rose Atoll|Swains Island|Western"; // 5 divisions (3 districts + Swains Island + Rose Atoll) — https://en.wikipedia.org/wiki/Administrative_divisions_of_American_Samoa
aStates["Andorra"] = "|Andorra la Vella|Canillo|Encamp|Escaldes-Engordany|La Massana|Ordino|Sant Julià de Lòria"; // 7 parishes — https://ca.wikipedia.org/wiki/Parr%C3%B2quies_d%27Andorra https://en.wikipedia.org/wiki/Parishes_of_Andorra
aStates["Angola"] = "|Bengo|Benguela|Bié|Cabinda|Cuando Cubango|Cuanza Norte|Cuanza Sul|Cunene|Huambo|Huíla|Luanda|Lunda Norte|Lunda Sul|Malanje|Moxico|Namibe|Uíge|Zaire"; // 18 provinces — https://pt.wikipedia.org/wiki/Prov%C3%ADncias_de_Angola https://en.wikipedia.org/wiki/Provinces_of_Angola
aStates["Anguilla"] = "|Blowing Point|East End|George Hill|Island Harbour|North Hill|North Side|Sandy Ground|Sandy Hill|South Hill|Stoney Ground|The Farrington|The Quarter|The Valley|West End"; // 14 districts — https://en.wikipedia.org/wiki/Anguilla#Administrative_divisions https://www.gov.ai/
aStates["Antarctica"] = "|Adélie Land|Argentine Antarctica|Australian Antarctic Territory|British Antarctic Territory|Chilean Antarctic Territory|Peter I Island|Queen Maud Land|Ross Dependency"; // 8 territorial claims (subject to the Antarctic Treaty) — https://en.wikipedia.org/wiki/Antarctica https://en.wikipedia.org/wiki/Territorial_claims_in_Antarctica
aStates["Antigua and Barbuda"] = "|Barbuda|Redonda|Saint George|Saint John|Saint Mary|Saint Paul|Saint Peter|Saint Philip"; // 8 (6 parishes + 2 dependencies) — https://en.wikipedia.org/wiki/Antigua_and_Barbuda#Administrative_divisions https://antigua-barbuda.org/government.htm
aStates["Argentina"] = "|Buenos Aires|Catamarca|Chaco|Chubut|Ciudad Autónoma de Buenos Aires|Córdoba|Corrientes|Entre Ríos|Formosa|Jujuy|La Pampa|La Rioja|Mendoza|Misiones|Neuquén|Río Negro|Salta|San Juan|San Luis|Santa Cruz|Santa Fe|Santiago del Estero|Tierra del Fuego, Antártida e Islas del Atlántico Sur|Tucumán"; // 24 (23 provinces + Autonomous City) — https://es.wikipedia.org/wiki/Provincias_de_Argentina https://en.wikipedia.org/wiki/Provinces_of_Argentina
aStates["Armenia"] = "|Aragatsotn|Ararat|Armavir|Gegharkunik|Kotayk|Lori|Shirak|Syunik|Tavush|Vayots Dzor|Yerevan"; // 11 (10 provinces + Yerevan) — https://hy.wikipedia.org/wiki/Հայաստանի_մարզեր https://en.wikipedia.org/wiki/Administrative_divisions_of_Armenia
aStates["Aruba"] = "|Noord|Oranjestad Oost|Oranjestad West|Paradera|San Nicolaas Noord|San Nicolaas Zuid|Santa Cruz|Savaneta"; // 8 regions (statistical/census regions) — https://nl.wikipedia.org/wiki/Aruba https://en.wikipedia.org/wiki/Aruba
aStates["Australia"] = "|Australian Capital Territory|New South Wales|Northern Territory|Queensland|South Australia|Tasmania|Victoria|Western Australia"; // 8 states and territories — https://en.wikipedia.org/wiki/States_and_territories_of_Australia
aStates["Austria"] = "|Burgenland|Kärnten|Niederösterreich|Oberösterreich|Salzburg|Steiermark|Tirol|Vorarlberg|Wien"; // 9 states — https://de.wikipedia.org/wiki/Bundesl%C3%A4nder_%C3%96sterreichs https://en.wikipedia.org/wiki/States_of_Austria
aStates["Azerbaijan"] = "|Absheron|Agdam|Agdash|Aghjabadi|Agstafa|Agsu|Astara|Babek|Baku|Balakan|Barda|Beylagan|Bilasuvar|Dashkasan|Fuzuli|Gadabay|Ganja|Gobustan|Goranboy|Goychay|Goygol|Hajigabul|Imishli|Ismayilli|Jabrayil|Jalilabad|Julfa|Kalbajar|Kangarli|Khachmaz|Khankendi|Khizi|Khojaly|Khojavend|Kurdamir|Lachin|Lankaran|Lankaran|Lerik|Masally|Mingachevir|Naftalan|Nakhchivan|Neftchala|Oghuz|Ordubad|Qabala|Qakh|Qazakh|Quba|Qubadli|Qusar|Saatly|Sabirabad|Sadarak|Salyan|Samukh|Shabran|Shahbuz|Shaki|Shaki|Shamakhi|Shamkir|Sharur|Shirvan|Shusha|Siyazan|Sumgait|Tartar|Tovuz|Ujar|Yardimli|Yevlakh|Yevlakh|Zagatala|Zangilan|Zardab"; // 77 administrative divisions (66 rayons + 11 cities) — https://az.wikipedia.org/wiki/Az%C9%99rbaycan%C4%B1n_iqtisadi_rayonlar%C4%B1 https://en.wikipedia.org/wiki/Administrative_divisions_of_Azerbaijan
aStates["Bahamas"] = "|Acklins|Berry Islands|Bimini|Cat Island|Central Abaco|Central Andros|Central Eleuthera|City of Freeport|Crooked Island|East Grand Bahama|Exuma|Exuma Cays|Grand Cay|Great Abaco Cays|Green Turtle Cay|Harbour Island|Inagua|Long Island|Mangrove Cay|Mayaguana|Moore's Island|North Abaco|North Andros|North Eleuthera|Ragged Island|Rum Cay|San Salvador|South Abaco|South Andros|South Eleuthera|Spanish Wells|Sweeting's Cay|West Grand Bahama"; // 32 districts — https://en.wikipedia.org/wiki/Administrative_divisions_of_the_Bahamas
aStates["Bahrain"] = "|Capital|Muharraq|Northern|Southern"; // 4 governorates — https://ar.wikipedia.org/wiki/%D9%85%D8%AD%D8%A7%D9%81%D8%B8%D8%A7%D8%AA_%D8%A7%D9%84%D8%A8%D8%AD%D8%B1%D9%8A%D9%86 https://en.wikipedia.org/wiki/Governorates_of_Bahrain
aStates["Bangladesh"] = "|Barishal|Chattogram|Dhaka|Khulna|Mymensingh|Rajshahi|Rangpur|Sylhet"; // 8 divisions — https://bn.wikipedia.org/wiki/%E0%A6%AC%E0%A6%BE%E0%A6%82%E0%A6%B2%E0%A6%BE%E0%A6%A6%E0%A7%87%E0%A6%B6%E0%A7%8D%E0%A6%8F%E0%A6%B0_%E0%A6%AC%E0%A6%BF%E0%A6%AD%E0%A6%BE%E0%A6%97%E0%A6%B8%E0%A6%AE%E0%A7%82%E0%A6%B9 https://en.wikipedia.org/wiki/Divisions_of_Bangladesh
aStates["Barbados"] = "|Christ Church|Saint Andrew|Saint George|Saint James|Saint John|Saint Joseph|Saint Lucy|Saint Michael|Saint Peter|Saint Philip|Saint Thomas"; // 11 parishes https://en.wikipedia.org/wiki/Parishes_of_Barbados
aStates["Belarus"] = "|Brest|Gomel|Grodno|Minsk City|Minsk Region|Mogilev|Vitebsk"; // 6 oblasts and 1 city https://en.wikipedia.org/wiki/Administrative_divisions_of_Belarus
aStates["Belgium"] = "|Antwerpen|Brabant Wallon|Brussels-Capital Region|Hainaut|Liège|Limburg|Luxembourg|Namur|Oost-Vlaanderen|Vlaams-Brabant|West-Vlaanderen"; // 10 provinces and 1 region https://en.wikipedia.org/wiki/Provinces_of_Belgium
aStates["Belize"] = "|Belize|Cayo|Corozal|Orange Walk|Stann Creek|Toledo"; // 6 districts https://en.wikipedia.org/wiki/Districts_of_Belize
aStates["Benin"] = "|Alibori|Atacora|Atlantique|Borgou|Collines|Couffo|Donga|Littoral|Mono|Ouémé|Plateau|Zou"; // 12 departments https://en.wikipedia.org/wiki/Departments_of_Benin
aStates["Bermuda"] = "|Devonshire|Hamilton|Paget|Pembroke|Sandys|Smith's|Southampton|St George's|Warwick"; // 9 parishes https://en.wikipedia.org/wiki/Parishes_of_Bermuda
aStates["Bhutan"] = "|Bumthang|Chukha|Dagana|Gasa|Haa|Lhuntse|Mongar|Paro|Pemagatshel|Punakha|Samdrup Jongkhar|Samtse|Sarpang|Thimphu|Trashigang|Trashiyangtse|Trongsa|Tsirang|Wangdue Phodrang|Zhemgang"; // 20 dzongkhags https://en.wikipedia.org/wiki/Dzongkhags_of_Bhutan
aStates["Bolivia"] = "|Beni|Chuquisaca|Cochabamba|La Paz|Oruro|Pando|Potosí|Santa Cruz|Tarija"; // 9 departments https://en.wikipedia.org/wiki/Departments_of_Bolivia
aStates["Bonaire, Sint Eustatius and Saba"] = "|Bonaire|Saba|Sint Eustatius"; // 3 Caribbean Netherlands special municipalities https://en.wikipedia.org/wiki/Caribbean_Netherlands
aStates["Bosnia and Herzegovina"] = "|Brčko Distrikt|Federacija Bosne i Hercegovine|Republika Srpska"; // 2 entities and 1 district https://en.wikipedia.org/wiki/Political_divisions_of_Bosnia_and_Herzegovina
aStates["Botswana"] = "|Central|Chobe|Francistown|Gaborone|Ghanzi|Jwaneng|Kgalagadi|Kgatleng|Kweneng|Lobatse|North-East|North-West|Orapa|Selebi-Phikwe|South-East|Southern|Sowa"; // 17 districts https://en.wikipedia.org/wiki/Districts_of_Botswana
aStates["Bouvet Island"] = "|Bouvet Island"; // 1 island https://en.wikipedia.org/wiki/Bouvet_Island
aStates["Brazil"] = "|Acre|Alagoas|Amapá|Amazonas|Bahia|Ceará|Distrito Federal|Espírito Santo|Goiás|Maranhão|Mato Grosso|Mato Grosso do Sul|Minas Gerais|Pará|Paraíba|Paraná|Pernambuco|Piauí|Rio de Janeiro|Rio Grande do Norte|Rio Grande do Sul|Rondônia|Roraima|Santa Catarina|São Paulo|Sergipe|Tocantins"; // 26 states and 1 federal district https://en.wikipedia.org/wiki/States_of_Brazil
aStates["British Indian Ocean Territory"] = "|Diego Garcia|Egmont Islands|Nelson Island|Peros Banhos|Salomon Islands|Three Brothers"; // 6 island groups https://en.wikipedia.org/wiki/British_Indian_Ocean_Territory
aStates["Brunei Darussalam"] = "|Belait|Brunei-Muara|Temburong|Tutong"; // 4 districts https://en.wikipedia.org/wiki/Districts_of_Brunei
aStates["Bulgaria"] = "|Blagoevgrad|Burgas|Dobrich|Gabrovo|Haskovo|Kardzhali|Kyustendil|Lovech|Montana|Pazardzhik|Pernik|Pleven|Plovdiv|Razgrad|Ruse|Shumen|Silistra|Sliven|Smolyan|Sofia|Sofia-grad|Stara Zagora|Targovishte|Varna|Veliko Tarnovo|Vidin|Vratsa|Yambol"; // 28 provinces https://en.wikipedia.org/wiki/Provinces_of_Bulgaria
aStates["Burkina Faso"] = "|Bale|Bam|Banwa|Bazega|Bougouriba|Boulgou|Boulkiemde|Comoe|Ganzourgou|Gnagna|Gourma|Houet|Ioba|Kadiogo|Kenedougou|Komondjari|Kompienga|Kossi|Koulpelogo|Kouritenga|Kourweogo|Leraba|Loroum|Mouhoun|Nahouri|Namentenga|Nayala|Noumbiel|Oubritenga|Oudalan|Passore|Poni|Sanguie|Sanmatenga|Seno|Sissili|Soum|Sourou|Tapoa|Tuy|Yagha|Yatenga|Ziro|Zondoma|Zoundweogo"; // 45 provinces https://en.wikipedia.org/wiki/Provinces_of_Burkina_Faso
aStates["Burundi"] = "|Buhumuza|Bujumbura|Burunga|Butanyerera|Gitega"; // 5 provinces based on the 2025 territorial reform https://en.wikipedia.org/wiki/Provinces_of_Burundi
aStates["Cabo Verde"] = "|Boa Vista|Brava|Maio|Mosteiros|Paul|Porto Novo|Praia|Ribeira Grande|Sal|Santa Catarina|Santa Cruz|Sao Domingos|Sao Filipe|Sao Nicolau|Sao Vicente|Tarrafal"; // 22 municipalities https://en.wikipedia.org/wiki/Municipalities_of_Cape_Verde
aStates["Cambodia"] = "|Banteay Mean Cheay|Batdambang|Kampong Cham|Kampong Chhnang|Kampong Spoe|Kampong Thum|Kampot|Kandal|Kaoh Kong|Keb|Kracheh|Mondol Kiri|Otdar Mean Cheay|Pailin|Phnum Penh|Pouthisat|Preah Seihanu (Sihanoukville)|Preah Vihear|Prey Veng|Rotanah Kiri|Siem Reab|Stoeng Treng|Svay Rieng|Takev"; // 25 provinces https://en.wikipedia.org/wiki/Provinces_of_Cambodia
aStates["Cameroon"] = "|Adamaoua|Centre|Est|Extrême-Nord|Littoral|Nord|Nord-Ouest|Ouest|Sud|Sud-Ouest"; // 10 regions (https://fr.wikipedia.org/wiki/R%C3%A9gions_du_Cameroun)
aStates["Canada"] = "|Alberta|British Columbia|Manitoba|New Brunswick|Newfoundland and Labrador|Northwest Territories|Nova Scotia|Nunavut|Ontario|Prince Edward Island|Quebec|Saskatchewan|Yukon"; // 10 provinces + 3 territories (https://en.wikipedia.org/wiki/Provinces_and_territories_of_Canada)
aStates["Cayman Islands"] = "|Bodden Town|Cayman Brac|East End|George Town|Little Cayman|North Side|West Bay"; // 6 districts (https://en.wikipedia.org/wiki/Geography_of_the_Cayman_Islands)
aStates["Central African Republic"] = "|Bamingui-Bangoran|Bangui|Basse-Kotto|Haut-Mbomou|Haute-Kotto|Kémo|Lim-Pendé|Lobaye|Mambéré|Mambéré-Kadéï|Mbomou|Nana-Grébizi|Nana-Mambéré|Ombella-M'Poko|Ouaka|Ouham|Ouham-Fafa|Ouham-Pendé|Sangha-Mbaéré|Vakaga"; // 20 prefectures (reform 2021) (https://fr.wikipedia.org/wiki/Pr%C3%A9fectures_de_la_R%C3%A9publique_centrafricaine)
aStates["Chad"] = "|Bahr el-Gazel|Batha|Borkou|Chari-Baguirmi|Ennedi-Est|Ennedi-Ouest|Guéra|Hadjer-Lamis|Kanem|Lac|Logone Occidental|Logone Oriental|Mandoul|Mayo-Kebbi Est|Mayo-Kebbi Ouest|Moyen-Chari|N'Djamena|Ouaddaï|Salamat|Sila|Tandjilé|Tibesti|Wadi Fira"; // 23 provinces (restructured 2018) (https://en.wikipedia.org/wiki/Provinces_of_Chad)
aStates["Chile"] = "|Antofagasta|Araucanía|Arica y Parinacota|Atacama|Aysén|Biobío|Coquimbo|Los Lagos|Los Ríos|Magallanes y de la Antártica Chilena|Maule|Metropolitana de Santiago|Ñuble|O'Higgins|Tarapacá|Valparaíso"; // 16 regions (restructured 2018) (https://es.wikipedia.org/wiki/Regiones_de_Chile)
aStates["China"] = "|Anhui|Beijing|Chongqing|Fujian|Gansu|Guangdong|Guangxi|Guizhou|Hainan|Hebei|Heilongjiang|Henan|Hubei|Hunan|Jiangsu|Jiangxi|Jilin|Liaoning|Nei Mongol|Ningxia|Qinghai|Shaanxi|Shandong|Shanghai|Shanxi|Sichuan|Tianjin|Xinjiang|Xizang (Tibet)|Yunnan|Zhejiang"; // 31 provinces/autonomous regions/municipalities (https://en.wikipedia.org/wiki/Administrative_divisions_of_China)
aStates["Christmas Island"] = "|Drumsite|Flying Fish Cove|Kampong|Lily Beach|Poon Saan|Settlement|Silver City"; // 7 settlements (https://en.wikipedia.org/wiki/Christmas_Island)
aStates["Cocos (Keeling) Islands"] = "|Direction Island|Home Island|Horsburgh Island|North Keeling Island|South Island|West Island"; // 6 islands (https://en.wikipedia.org/wiki/Cocos_(Keeling)_Islands)
aStates["Colombia"] = "|Amazonas|Antioquia|Arauca|Atlantico|Bolivar|Boyaca|Caldas|Caqueta|Casanare|Cauca|Cesar|Choco|Cordoba|Cundinamarca|Distrito Capital de Santa Fe de Bogota|Guainia|Guaviare|Huila|La Guajira|Magdalena|Meta|Narino|Norte de Santander|Putumayo|Quindio|Risaralda|San Andres y Providencia|Santander|Sucre|Tolima|Valle del Cauca|Vaupes|Vichada"; // 32 departments + capital district (https://es.wikipedia.org/wiki/Departamentos_de_Colombia)
aStates["Comoros"] = "|Anjouan (Nzwani)|Grande Comore (Njazidja)|Moheli (Mwali)"; // 3 autonomous islands (https://fr.wikipedia.org/wiki/%C3%8Eles_autonomes_des_Comores)
aStates["Congo"] = "|Bouenza|Brazzaville|Congo-Oubangui|Cuvette|Cuvette-Ouest|Djoué-Léfini|Kouilou|Lékoumou|Likouala|Niari|Nkéni-Alima|Plateaux|Pointe-Noire|Pool|Sangha"; // 15 departments (updated October 2024) (https://fr.wikipedia.org/wiki/D%C3%A9partements_du_Congo)
aStates["Democratic Republic of the Congo"] = "|Bas-Uele|Équateur|Haut-Katanga|Haut-Lomami|Haut-Uele|Ituri|Kasaï|Kasaï-Central|Kasaï-Oriental|Kinshasa|Kongo Central|Kwango|Kwilu|Lomami|Lualaba|Mai-Ndombe|Maniema|Mongala|Nord-Kivu|Nord-Ubangi|Sankuru|Sud-Kivu|Sud-Ubangi|Tanganyika|Tshopo|Tshuapa"; // 26 provinces (restructured 2015) (https://fr.wikipedia.org/wiki/Provinces_de_la_R%C3%A9publique_d%C3%A9mocratique_du_Congo)
aStates["Cook Islands"] = "|Aitutaki|Atiu|Mangaia|Manihiki|Manuae|Mauke|Mitiaro|Nassau|Palmerston|Penrhyn|Pukapuka|Rakahanga|Rarotonga|Suwarrow|Takutea"; // 15 islands (https://en.wikipedia.org/wiki/Cook_Islands)
aStates["Costa Rica"] = "|Alajuela|Cartago|Guanacaste|Heredia|Limon|Puntarenas|San Jose"; // 7 provinces (https://es.wikipedia.org/wiki/Provincias_de_Costa_Rica)
aStates["Côte d'Ivoire"] = "|Abengourou|Abidjan|Aboisso|Adiake'|Adzope|Agboville|Agnibilekrou|Ale'pe'|Bangolo|Beoumi|Biankouma|Bocanda|Bondoukou|Bongouanou|Bouafle|Bouake|Bouna|Boundiali|Dabakala|Dabon|Daloa|Danane|Daoukro|Dimbokro|Divo|Duekoue|Ferkessedougou|Gagnoa|Grand Bassam|Grand-Lahou|Guiglo|Issia|Jacqueville|Katiola|Korhogo|Lakota|Man|Mankono|Mbahiakro|Odienne|Oume|Sakassou|Sassandra|Seguela|Sinfra|Soubre|Tabou|Tanda|Tiassale|Tiebissou|Tingrela|Touba|Toulepleu|Toumodi|Vavoua|Yamoussoukro|Zuenoula"; // 60 departments (https://fr.wikipedia.org/wiki/Districts_de_C%C3%B4te_d%27Ivoire)
aStates["Croatia"] = "|Bjelovarsko-bilogorska županija|Brodsko-posavska županija|Dubrovačko-neretvanska županija|Istarska županija|Karlovačka županija|Koprivničko-križevačka županija|Krapinsko-zagorska županija|Ličko-senjska županija|Međimurska županija|Osječko-baranjska županija|Požeško-slavonska županija|Primorsko-goranska županija|Šibensko-kninska županija|Sisačko-moslavačka županija|Splitsko-dalmatinska županija|Varaždinska županija|Virovitičko-podravska županija|Vukovarsko-srijemska županija|Zadarska županija|Zagreb|Zagrebačka županija"; // 20 counties + Zagreb city (https://en.wikipedia.org/wiki/Counties_of_Croatia)
aStates["Cuba"] = "|Artemisa|Camaguey|Ciego de Avila|Cienfuegos|Granma|Guantanamo|Holguin|Isla de la Juventud|La Habana|Las Tunas|Matanzas|Mayabeque|Pinar del Rio|Sancti Spiritus|Santiago de Cuba|Villa Clara"; // 15 provinces + 1 special municipality (https://es.wikipedia.org/wiki/Divisi%C3%B3n_territorial_de_Cuba)
aStates["Curaçao"] = "|Bandabou|Bandariba|Willemstad"; // 1 city district + 2 outer districts (https://en.wikipedia.org/wiki/Cura%C3%A7ao)
aStates["Cyprus"] = "|Famagusta|Kyrenia|Larnaca|Limassol|Nicosia|Paphos"; // 6 districts (https://en.wikipedia.org/wiki/Districts_of_Cyprus)
aStates["Czechia"] = "|Hlavní město Praha|Jihočeský kraj|Jihomoravský kraj|Karlovarský kraj|Kraj Vysočina|Královéhradecký kraj|Liberecký kraj|Moravskoslezský kraj|Olomoucký kraj|Pardubický kraj|Plzeňský kraj|Středočeský kraj|Ústecký kraj|Zlínský kraj"; // 1 capital city + 13 regions (https://en.wikipedia.org/wiki/Regions_of_the_Czech_Republic)
aStates["Denmark"] = "|Hovedstaden|Midtjylland|Nordjylland|Sjælland|Syddanmark"; // 5 regions (https://en.wikipedia.org/wiki/Regions_of_Denmark)
aStates["Djibouti"] = "|Ali Sabieh|Arta|Dikhil|Djibouti|Obock|Tadjourah"; // 6 regions (https://en.wikipedia.org/wiki/Regions_of_Djibouti)
aStates["Dominica"] = "|Saint Andrew|Saint David|Saint George|Saint John|Saint Joseph|Saint Luke|Saint Mark|Saint Patrick|Saint Paul|Saint Peter"; // 10 parishes (https://en.wikipedia.org/wiki/Parishes_of_Dominica)
aStates["Dominican Republic"] = "|Azua|Baoruco|Barahona|Dajabón|Distrito Nacional|Duarte|El Seibo|Elías Piña|Espaillat|Hato Mayor|Hermanas Mirabal|Independencia|La Altagracia|La Romana|La Vega|María Trinidad Sánchez|Monseñor Nouel|Monte Cristi|Monte Plata|Pedernales|Peravia|Puerto Plata|Samaná|San Cristóbal|San José de Ocoa|San Juan|San Pedro de Macorís|Sánchez Ramírez|Santiago|Santiago Rodríguez|Santo Domingo|Valverde"; // 31 provinces + 1 national district (https://en.wikipedia.org/wiki/Provinces_of_the_Dominican_Republic)
aStates["Ecuador"] = "|Azuay|Bolívar|Cañar|Carchi|Chimborazo|Cotopaxi|El Oro|Esmeraldas|Galápagos|Guayas|Imbabura|Loja|Los Ríos|Manabí|Morona Santiago|Napo|Orellana|Pastaza|Pichincha|Santa Elena|Santo Domingo de los Tsáchilas|Sucumbíos|Tungurahua|Zamora Chinchipe"; // 24 provinces (https://en.wikipedia.org/wiki/Provinces_of_Ecuador)
aStates["Egypt"] = "|Alexandria|Aswan|Asyut|Beheira|Beni Suef|Cairo|Dakahlia|Damietta|Faiyum|Gharbia|Giza|Ismailia|Kafr El Sheikh|Luxor|Matrouh|Minya|Monufia|New Valley|North Sinai|Port Said|Qalyubia|Qena|Red Sea|Sharqia|Sohag|South Sinai|Suez"; // 27 governorates (https://en.wikipedia.org/wiki/Governorates_of_Egypt)
aStates["El Salvador"] = "|Ahuachapán|Cabañas|Chalatenango|Cuscatlán|La Libertad|La Paz|La Unión|Morazán|San Miguel|San Salvador|San Vicente|Santa Ana|Sonsonate|Usulután"; // 14 departments (https://en.wikipedia.org/wiki/Departments_of_El_Salvador)
aStates["Equatorial Guinea"] = "|Annobón|Bioko Norte|Bioko Sur|Centro Sur|Djibloho|Kié-Ntem|Litoral|Wele-Nzas"; // 8 provinces (https://en.wikipedia.org/wiki/Provinces_of_Equatorial_Guinea)
aStates["Eritrea"] = "|Anseba Region|Central Region|Debub Region|Gash-Barka Region|Northern Red Sea Region|Southern Red Sea Region"; // 6 regions (https://en.wikipedia.org/wiki/Regions_of_Eritrea)
aStates["Estonia"] = "|Harju County|Hiiu County|Ida-Viru County|Järva County|Jõgeva County|Lääne County|Lääne-Viru County|Pärnu County|Põlva County|Rapla County|Saare County|Tartu County|Valga County|Viljandi County|Võru County"; // 15 counties (https://en.wikipedia.org/wiki/Counties_of_Estonia)
aStates["Eswatini"] = "|Hhohho|Lubombo|Manzini|Shiselweni"; // 4 regions (https://en.wikipedia.org/wiki/Regions_of_Eswatini)  
aStates["Ethiopia"] = "|Addis Ababa|Afar Region|Amhara Region|Benishangul-Gumuz Region|Central Ethiopia Regional State|Dire Dawa|Gambela Region|Harari Region|Oromia Region|Sidama Region|Somali Region|South Ethiopia Regional State|South West Ethiopia Peoples' Region|Tigray Region"; // 12 regions and 2 chartered cities based on the 2023 administrative reform (https://en.wikipedia.org/wiki/Regions_of_Ethiopia)
aStates["Falkland Islands (Islas Malvinas)"] = "|East Falkland|Stanley|West Falkland"; // 2 main islands and capital (https://en.wikipedia.org/wiki/Falkland_Islands)
aStates["Faroe Islands"] = "|Eysturoy|Norðoyar|Sandoy|Streymoy|Suðuroy|Vágar"; // 6 traditional regions (https://en.wikipedia.org/wiki/Regions_of_the_Faroe_Islands)
aStates["Fiji"] = "|Central|Eastern|Northern|Rotuma|Western"; // 5 divisions (https://en.wikipedia.org/wiki/Divisions_of_Fiji)
aStates["Finland"] = "|Åland|Etelä-Karjala|Etelä-Pohjanmaa|Etelä-Savo|Kainuu|Kanta-Häme|Keski-Pohjanmaa|Keski-Suomi|Kymenlaakso|Lappi|Päijät-Häme|Pirkanmaa|Pohjanmaa|Pohjois-Karjala|Pohjois-Pohjanmaa|Pohjois-Savo|Satakunta|Uusimaa|Varsinais-Suomi"; // 19 regions (https://en.wikipedia.org/wiki/Regions_of_Finland)
aStates["France"] = "|Auvergne-Rhône-Alpes|Bourgogne-Franche-Comté|Bretagne|Centre-Val de Loire|Corse|Grand Est|Guadeloupe|Guyane|Hauts-de-France|Île-de-France|Martinique|Mayotte|Normandie|Nouvelle-Aquitaine|Occitanie|Pays de la Loire|Provence-Alpes-Côte d'Azur|Réunion"; // 18 regions (https://en.wikipedia.org/wiki/Regions_of_France)
aStates["French Guiana"] = "|Apatou|Awala-Yalimapo|Camopi|Cayenne|Grand-Santi|Iracoubo|Kourou|Macouria|Mana|Maripasoula|Matoury|Montsinery-Tonnegrande|Ouanary|Papaichton|Régina|Remire-Montjoly|Roura|Saint-Élie|Saint-Georges|Saint-Laurent-du-Maroni|Saül|Sinnamary"; // 22 communes (https://en.wikipedia.org/wiki/Communes_of_French_Guiana)
aStates["French Polynesia"] = "|Îles Australes|Îles du Vent|Îles Marquises|Îles Sous-le-Vent|Îles Tuamotu-Gambier"; // 5 administrative subdivisions (https://en.wikipedia.org/wiki/Administrative_divisions_of_French_Polynesia)
aStates["French Southern Territories"] = "|Îles Crozet|Îles Éparses|Îles Kerguelen|Îles Saint-Paul-et-Amsterdam|Terre Adélie"; // 5 districts (https://en.wikipedia.org/wiki/French_Southern_and_Antarctic_Lands)
aStates["Gabon"] = "|Estuaire|Haut-Ogooué|Moyen-Ogooué|Ngounié|Nyanga|Ogooué-Ivindo|Ogooué-Lolo|Ogooué-Maritime|Woleu-Ntem"; // 9 provinces (https://en.wikipedia.org/wiki/Provinces_of_Gabon)
aStates["Gambia"] = "|Banjul|Basse|Brikama|Janjanbureh|Kanifing|Kerewan|Kuntaur|Mansakonko"; // 8 local government areas (https://en.wikipedia.org/wiki/Local_government_areas_of_The_Gambia)
aStates["Georgia"] = "|Abkhazia|Adjara|Guria|Imereti|Kakheti|Kvemo Kartli|Mtskheta-Mtianeti|Racha-Lechkhumi and Kvemo Svaneti|Samegrelo-Zemo Svaneti|Samtskhe-Javakheti|Shida Kartli|Tbilisi"; // 2 autonomous republics + 9 regions + capital (https://en.wikipedia.org/wiki/Administrative_divisions_of_Georgia_(country))
aStates["Germany"] = "|Baden-Württemberg|Bayern|Berlin|Brandenburg|Bremen|Hamburg|Hessen|Mecklenburg-Vorpommern|Niedersachsen|Nordrhein-Westfalen|Rheinland-Pfalz|Saarland|Sachsen|Sachsen-Anhalt|Schleswig-Holstein|Thüringen"; // 16 federal states (https://en.wikipedia.org/wiki/States_of_Germany)
aStates["Ghana"] = "|Ahafo|Ashanti|Bono|Bono East|Central|Eastern|Greater Accra|North East|Northern|Oti|Savannah|Upper East|Upper West|Volta|Western|Western North"; // 16 regions (https://en.wikipedia.org/wiki/Regions_of_Ghana)
aStates["Gibraltar"] = "|Gibraltar"; // No administrative divisions (British Overseas Territory) (https://en.wikipedia.org/wiki/Gibraltar)
aStates["Greece"] = "|Attica|Central Greece|Central Macedonia|Crete|Eastern Macedonia and Thrace|Epirus|Ionian Islands|Mount Athos|North Aegean|Peloponnese|South Aegean|Thessaly|Western Greece|Western Macedonia"; // 13 regions + 1 autonomous region (https://en.wikipedia.org/wiki/Regions_of_Greece)
aStates["Greenland"] = "|Avannaata|Kujalleq|Qeqertalik|Qeqqata|Sermersooq"; // 5 municipalities (https://en.wikipedia.org/wiki/Municipalities_of_Greenland)
aStates["Grenada"] = "|Carriacou and Petite Martinique|Saint Andrew|Saint David|Saint George|Saint John|Saint Mark|Saint Patrick"; // 6 parishes + 1 dependency (https://en.wikipedia.org/wiki/Parishes_of_Grenada)
aStates["Guam"] = "|Agana Heights|Agat|Asan-Maina|Barrigada|Chalan Pago-Ordot|Dededo|Hagatna|Inarajan|Mangilao|Merizo|Mongmong-Toto-Maite|Piti|Santa Rita|Sinajana|Talofofo|Tamuning-Tumon-Harmon|Umatac|Yigo|Yona"; // 19 villages (https://en.wikipedia.org/wiki/Villages_of_Guam)
aStates["Guadeloupe"] = "|Anse-Bertrand|Baie-Mahault|Baillif|Basse-Terre|Bouillante|Capesterre-Belle-Eau|Capesterre-de-Marie-Galante|Deshaies|Gourbeyre|Goyave|Grand-Bourg|La Désirade|Lamentin|Le Gosier|Le Moule|Les Abymes|Morne-à-l'Eau|Petit-Bourg|Petit-Canal|Pointe-à-Pitre|Pointe-Noire|Port-Louis|Saint-Claude|Saint-François|Saint-Louis|Sainte-Anne|Sainte-Rose|Terre-de-Bas|Terre-de-Haut|Trois-Rivières|Vieux-Fort|Vieux-Habitants"; // 32 communes (https://en.wikipedia.org/wiki/Communes_of_Guadeloupe)
aStates["Guatemala"] = "|Alta Verapaz|Baja Verapaz|Chimaltenango|Chiquimula|El Progreso|Escuintla|Guatemala|Huehuetenango|Izabal|Jalapa|Jutiapa|Petén|Quetzaltenango|Quiché|Retalhuleu|Sacatepéquez|San Marcos|Santa Rosa|Sololá|Suchitepéquez|Totonicapán|Zacapa"; // 22 departments (https://en.wikipedia.org/wiki/Departments_of_Guatemala)
aStates["Guernsey"] = "|Castel|Forest|Saint Andrew|Saint Martin|Saint Peter Port|Saint Pierre du Bois|Saint Sampson|Saint Saviour|Torteval|Vale"; // 10 parishes (https://en.wikipedia.org/wiki/Parishes_of_Guernsey)
aStates["Guinea"] = "|Boké|Conakry|Faranah|Kankan|Kindia|Labé|Mamou|Nzérékoré"; // 8 regions including special zone (https://en.wikipedia.org/wiki/Regions_of_Guinea)
aStates["Guinea-Bissau"] = "|Bafatá|Biombo|Bissau|Bolama|Cacheu|Gabú|Oio|Quinara|Tombali"; // 8 regions + 1 autonomous sector (https://en.wikipedia.org/wiki/Regions_of_Guinea-Bissau)
aStates["Guyana"] = "|Barima-Waini|Cuyuni-Mazaruni|Demerara-Mahaica|East Berbice-Corentyne|Essequibo Islands-West Demerara|Mahaica-Berbice|Pomeroon-Supenaam|Potaro-Siparuni|Upper Demerara-Berbice|Upper Takutu-Upper Essequibo"; // 10 regions (https://en.wikipedia.org/wiki/Regions_of_Guyana)
aStates["Haiti"] = "|Artibonite|Centre|Grand'Anse|Nippes|Nord|Nord-Est|Nord-Ouest|Ouest|Sud|Sud-Est"; // 10 departments (https://en.wikipedia.org/wiki/Departments_of_Haiti)
aStates["Heard Island and McDonald Islands"] = "|Heard Island|McDonald Islands"; // 2 islands (https://en.wikipedia.org/wiki/Heard_Island_and_McDonald_Islands)
aStates["Holy See"] = "|Vatican City"; // 1 city-state (https://en.wikipedia.org/wiki/Vatican_City)
aStates["Honduras"] = "|Atlántida|Choluteca|Colón|Comayagua|Copán|Cortés|El Paraíso|Francisco Morazán|Gracias a Dios|Intibucá|Islas de la Bahía|La Paz|Lempira|Ocotepeque|Olancho|Santa Bárbara|Valle|Yoro"; // 18 departments (https://en.wikipedia.org/wiki/Departments_of_Honduras)
aStates["Hong Kong"] = "|Central and Western|Eastern|Islands|Kowloon City|Kwai Tsing|Kwun Tong|North|Sai Kung|Sha Tin|Sham Shui Po|Southern|Tai Po|Tsuen Wan|Tuen Mun|Wan Chai|Wong Tai Sin|Yau Tsim Mong|Yuen Long"; // 18 administrative districts (https://en.wikipedia.org/wiki/Districts_of_Hong_Kong)
aStates["Hungary"] = "|Bács-Kiskun|Baranya|Békés|Borsod-Abaúj-Zemplén|Budapest|Csongrád-Csanád|Fejér|Győr-Moson-Sopron|Hajdú-Bihar|Heves|Jász-Nagykun-Szolnok|Komárom-Esztergom|Nógrád|Pest|Somogy|Szabolcs-Szatmár-Bereg|Tolna|Vas|Veszprém|Zala"; // 19 counties and 1 capital city (https://en.wikipedia.org/wiki/Counties_of_Hungary)
aStates["Iceland"] = "|Akranes|Akureyri|Árborg|Árneshreppur|Ásahreppur|Bláskógabyggð|Bolungarvík|Borgarbyggð|Dalabyggð|Dalvíkurbyggð|Eyja- og Miklaholtshreppur|Eyjafjarðarsveit|Fjallabyggð|Fjarðabyggð|Fljótsdalshreppur|Flóahreppur|Garðabær|Grímsnes- og Grafningshreppur|Grindavík|Grýtubakkahreppur|Hafnarfjörður|Hörgársveit|Hornafjörður|Hrunamannahreppur|Húnabyggð|Húnaþing vestra|Hvalfjarðarsveit|Hveragerði|Ísafjarðarbær|Kaldrananeshreppur|Kjósarhreppur|Kópavogur|Langanesbyggð|Mosfellsbær|Múlaþing|Mýrdalshreppur|Norðurþing|Ölfus|Rangárþing eystra|Rangárþing ytra|Reykhólahreppur|Reykjanesbær|Reykjavík|Seltjarnarnes|Skaftárhreppur|Skagafjörður|Skagaströnd|Skeiða- og Gnúpverjahreppur|Snæfellsbær|Strandabyggð|Stykkishólmur|Súðavík|Suðurnesjabær|Svalbarðsstrandarhreppur|Tjörneshreppur|Vestmannaeyjar|Vesturbyggð|Vogar|Vopnafjarðarhreppur|Þingeyjarsveit"; // 62 municipalities (https://en.wikipedia.org/wiki/List_of_municipalities_of_Iceland)
aStates["India"] = "|Andaman and Nicobar Islands|Andhra Pradesh|Arunachal Pradesh|Assam|Bihar|Chandigarh|Chhattisgarh|Dadra and Nagar Haveli and Daman and Diu|Delhi|Goa|Gujarat|Haryana|Himachal Pradesh|Jammu and Kashmir|Jharkhand|Karnataka|Kerala|Ladakh|Lakshadweep|Madhya Pradesh|Maharashtra|Manipur|Meghalaya|Mizoram|Nagaland|Odisha|Puducherry|Punjab|Rajasthan|Sikkim|Tamil Nadu|Telangana|Tripura|Uttar Pradesh|Uttarakhand|West Bengal"; // 28 states and 8 union territories (https://en.wikipedia.org/wiki/States_and_union_territories_of_India)
aStates["Indonesia"] = "|Aceh|Bali|Bangka Belitung Islands|Banten|Bengkulu|Central Java|Central Kalimantan|Central Papua|Central Sulawesi|East Java|East Kalimantan|East Nusa Tenggara|Gorontalo|Highland Papua|Jakarta|Jambi|Lampung|Maluku|North Kalimantan|North Maluku|North Sulawesi|North Sumatra|Papua|Riau|Riau Islands|South Kalimantan|South Papua|South Sulawesi|South Sumatra|Southeast Sulawesi|Southwest Papua|West Java|West Kalimantan|West Nusa Tenggara|West Papua|West Sulawesi|West Sumatra|Yogyakarta"; // 38 provinces (https://en.wikipedia.org/wiki/Provinces_of_Indonesia)
aStates["Iran"] = "|Alborz|Ardabil|Azarbayjan-e Gharbi|Azarbayjan-e Sharqi|Bushehr|Chahar Mahall va Bakhtiari|Esfahan|Fars|Gilan|Golestan|Hamadan|Hormozgan|Ilam|Kerman|Kermanshah|Khorasan-e Jonubi|Khorasan-e Razavi|Khorasan-e Shomali|Khuzestan|Kohgiluyeh va Buyer Ahmad|Kordestan|Lorestan|Markazi|Mazandaran|Qazvin|Qom|Semnan|Sistan va Baluchestan|Tehran|Yazd|Zanjan"; // 31 provinces (https://en.wikipedia.org/wiki/Provinces_of_Iran)
aStates["Iraq"] = "|Al Anbar|Al Basrah|Al Muthanna|Al Qadisiyah|An Najaf|Arbil|As Sulaymaniyah|At Ta'mim|Babil|Baghdad|Dahuk|Dhi Qar|Diyala|Karbala'|Maysan|Ninawa|Salah ad Din|Wasit"; // 18 governorates (https://en.wikipedia.org/wiki/Governorates_of_Iraq)
aStates["Ireland"] = "|Antrim|Armargh|Carlow|Cavan|Clare|Cork|Derry|Donegal|Down|Dublin|Dún Laoghaire–Rathdown|Fermanagh|Fingal|Galway|Kerry|Kildare|Kilkenny|Laois|Leitrim|Limerick|Longford|Louth|Mayo|Meath|Monaghan|Offaly|Roscommon|Sligo|Tipperary|Tyrone|Waterford|Westmeath|Wexford|Wicklow"; // 32 counties (https://en.wikipedia.org/wiki/Counties_of_Ireland)
aStates["Isle of Man"] = "|Andreas|Arbory|Ballaugh|Braddan|Bride|Castletown|Douglas|German|Jurby|Laxey|Lezayre|Lonan|Malew|Marown|Maughold|Michael|Onchan|Patrick|Peel|Port Erin|Port St Mary|Ramsey|Rushen|Santon"; // 17 parishes (https://en.wikipedia.org/wiki/Parishes_of_the_Isle_of_Man)
aStates["Israel"] = "|Central|Haifa|Jerusalem|Northern|Southern|Tel Aviv"; // 6 districts (https://en.wikipedia.org/wiki/Districts_of_Israel)
aStates["Italy"] = "|Abruzzo|Basilicata|Calabria|Campania|Emilia-Romagna|Friuli-Venezia Giulia|Lazio|Liguria|Lombardia|Marche|Molise|Piemonte|Puglia|Sardegna|Sicilia|Toscana|Trentino-Alto Adige|Umbria|Valle d'Aosta|Veneto"; // 20 regions (https://en.wikipedia.org/wiki/Regions_of_Italy)
aStates["Jamaica"] = "|Clarendon|Hanover|Kingston|Manchester|Portland|Saint Andrew|Saint Ann|Saint Catherine|Saint Elizabeth|Saint James|Saint Mary|Saint Thomas|Trelawny|Westmoreland"; // 14 parishes (https://en.wikipedia.org/wiki/Parishes_of_Jamaica)
aStates["Japan"] = "|Aichi|Akita|Aomori|Chiba|Ehime|Fukui|Fukuoka|Fukushima|Gifu|Gunma|Hiroshima|Hokkaido|Hyogo|Ibaraki|Ishikawa|Iwate|Kagawa|Kagoshima|Kanagawa|Kochi|Kumamoto|Kyoto|Mie|Miyagi|Miyazaki|Nagano|Nagasaki|Nara|Niigata|Oita|Okayama|Okinawa|Osaka|Saga|Saitama|Shiga|Shimane|Shizuoka|Tochigi|Tokushima|Tokyo|Tottori|Toyama|Wakayama|Yamagata|Yamaguchi|Yamanashi"; // 47 prefectures (https://en.wikipedia.org/wiki/Prefectures_of_Japan)
aStates["Jersey"] = "|Grouville|Saint Brelade|Saint Clement|Saint Helier|Saint John|Saint Lawrence|Saint Martin|Saint Mary|Saint Ouen|Saint Peter|Saint Saviour|Trinity"; // 12 parishes (https://en.wikipedia.org/wiki/Parishes_of_Jersey)
aStates["Jordan"] = "|'Amman|Ajlun|Al 'Aqabah|Al Balqa'|Al Karak|Al Mafraq|At Tafilah|Az Zarqa'|Irbid|Jarash|Ma'an|Madaba"; // 12 governorates (https://en.wikipedia.org/wiki/Governorates_of_Jordan)
aStates["Kazakhstan"] = "|Abai|Almaty|Aqmola|Aqtobe|Astana|Atyrau|Batys Qazaqstan|Bayqongyr|Jetisu|Mangghystau|Pavlodar|Qaraghandy|Qostanay|Qyzylorda|Shyghys Qazaqstan|Soltustik Qazaqstan|Turkistan|Ulytau|Zhambyl"; // 17 regions and 3 cities (https://en.wikipedia.org/wiki/Regions_of_Kazakhstan)
aStates["Kenya"] = "|Baringo|Bomet|Bungoma|Busia|Elgeyo-Marakwet|Embu|Garissa|Homa Bay|Isiolo|Kajiado|Kakamega|Kericho|Kiambu|Kilifi|Kirinyaga|Kisii|Kisumu|Kitui|Kwale|Laikipia|Lamu|Machakos|Makueni|Mandera|Marsabit|Meru|Migori|Mombasa|Murang'a|Nairobi|Nakuru|Nandi|Narok|Nyamira|Nyandarua|Nyeri|Samburu|Siaya|Taita-Taveta|Tana River|Tharaka-Nithi|Trans-Nzoia|Turkana|Uasin Gishu|Vihiga|Wajir|West Pokot"; // 47 counties (https://en.wikipedia.org/wiki/Counties_of_Kenya)
aStates["Kiribati"] = "|Abaiang|Abemama|Aranuka|Arorae|Banaba|Beru|Butaritari|Canton|Kiritimati|Kuria|Maiana|Makin|Marakei|Nikunau|Nonouti|Onotoa|Tabiteuea|Tabuaeran|Tamana|Tarawa|Teraina"; // 21 inhabited islands (https://en.wikipedia.org/wiki/Administrative_divisions_of_Kiribati)
aStates["North Korea"] = "|Chagang-do (Chagang Province)|Hamgyong-bukto (North Hamgyong Province)|Hamgyong-namdo (South Hamgyong Province)|Hwanghae-bukto (North Hwanghae Province)|Hwanghae-namdo (South Hwanghae Province)|Kaesong-si (Kaesong City)|Kangwon-do (Kangwon Province)|Namp'o-si (Namp'o City)|P'yongan-bukto (North P'yongan Province)|P'yongan-namdo (South P'yongan Province)|P'yongyang-si (P'yongyang City)|Rason-si (Rason City)|Ryanggang-do (Ryanggang Province)"; // 13 administrative divisions - 9 provinces + 4 special cities (https://en.wikipedia.org/wiki/Administrative_divisions_of_North_Korea)
aStates["South Korea"] = "|Ch'ungch'ong-bukto|Ch'ungch'ong-namdo|Cheju-do|Cholla-bukto|Cholla-namdo|Inch'on-gwangyoksi|Kangwon-do|Kwangju-gwangyoksi|Kyonggi-do|Kyongsang-bukto|Kyongsang-namdo|Pusan-gwangyoksi|Soul-t'ukpyolsi|Taegu-gwangyoksi|Taejon-gwangyoksi|Ulsan-gwangyoksi"; // 16 administrative divisions (https://en.wikipedia.org/wiki/Administrative_divisions_of_South_Korea)
aStates["Kosovo"] = "|Ferizaj|Gjakova|Gjilan|Mitrovica|Peja|Prishtina|Prizren"; // 7 districts (https://en.wikipedia.org/wiki/Districts_of_Kosovo)
aStates["Kuwait"] = "|Al Ahmadi|Al Asimah|Al Farwaniya|Al Jahra|Hawalli|Mubarak Al-Kabeer"; // 6 governorates (https://en.wikipedia.org/wiki/Governorates_of_Kuwait)
aStates["Kyrgyzstan"] = "|Batken|Bishkek|Chüy|Issyk-Kul|Jalal-Abad|Naryn|Osh City|Osh Region|Talas"; // 7 regions + 2 independent cities (https://en.wikipedia.org/wiki/Regions_of_Kyrgyzstan)
aStates["Laos"] = "|Attapeu|Bokèo|Bolikhamxai|Champasak|Houaphanh|Khammouane|Luang Namtha|Luang Prabang|Oudomxay|Phongsaly|Sainyabuli|Salavan|Savannakhet|Sekong|Vientiane Capital|Vientiane Province|Xaisomboun|Xiangkhouang"; // 18 provinces (17 + 1 prefecture) (https://en.wikipedia.org/wiki/Provinces_of_Laos)
aStates["Latvia"] = "|Ādaži|Aizkraukle|Alūksne|Augšdaugava|Balvi|Bauska|Cēsis|Dobele|Gulbene|Jēkabpils|Jelgava|Ķekava|Krāslava|Kuldīga|Limbaži|Līvāni|Ludza|Madona|Mārupe|Ogre|Olaine|Preiļi|Rēzekne|Ropaži|Salaspils|Saldus|Saulkrasti|Sigulda|Smiltene|South Kurzeme|Talsi|Tukums|Valka|Valmiera|Ventspils"; // 42 local government units (7 state cities + 35 municipalities) (https://en.wikipedia.org/wiki/Administrative_divisions_of_Latvia)
aStates["Lebanon"] = "|Akkar|Baalbek-Hermel|Beirut|Beqaa|Keserwan-Jbeil|Mount Lebanon|Nabatieh|North|South"; // 9 governorates (https://en.wikipedia.org/wiki/Governorates_of_Lebanon)
aStates["Lesotho"] = "|Berea|Butha-Buthe|Leribe|Mafeteng|Maseru|Mohale's Hoek|Mokhotlong|Qacha's Nek|Quthing|Thaba-Tseka"; // 10 districts (https://en.wikipedia.org/wiki/Districts_of_Lesotho)
aStates["Liberia"] = "|Bomi|Bong|Gbarpolu|Grand Bassa|Grand Cape Mount|Grand Gedeh|Grand Kru|Lofa|Margibi|Maryland|Montserrado|Nimba|River Gee|Rivercess|Sinoe"; // 15 counties (https://en.wikipedia.org/wiki/Counties_of_Liberia)
aStates["Libya"] = "|Al Butnan|Al Jabal al Akhdar|Al Jabal al Gharbi|Al Jafarah|Al Jufrah|Al Kufrah|Al Marj|Al Wahat|An Nuqat al Khams|Az Zawiyah|Benghazi|Darnah|Ghat|Marqab|Misrata|Murzuq|Nalut|Sabha|Surt|Tarabulus|Wadi al Hayat|Wadi ash Shatii"; // 22 districts (current system since 2007) (https://en.wikipedia.org/wiki/Districts_of_Libya)
aStates["Liechtenstein"] = "|Balzers|Eschen|Gamprin|Mauren|Planken|Ruggell|Schaan|Schellenberg|Triesen|Triesenberg|Vaduz"; // 11 municipalities (Gemeinden) - verified 2025-01-16 via Wikipedia (https://en.wikipedia.org/wiki/List_of_municipalities_of_Liechtenstein)
aStates["Lithuania"] = "|Alytus|Kaunas|Klaipėda|Marijampolė|Panevėžys|Šiauliai|Tauragė|Telšiai|Utena|Vilnius"; // 10 counties (apskritys) - verified 2025-01-16 via Wikipedia (https://en.wikipedia.org/wiki/Counties_of_Lithuania)
aStates["Luxembourg"] = "|Capellen|Clervaux|Diekirch|Echternach|Esch-sur-Alzette|Grevenmacher|Luxembourg|Mersch|Redange|Remich|Vianden|Wiltz"; // 12 cantons - verified 2025-01-16 via Wikipedia (https://en.wikipedia.org/wiki/Cantons_of_Luxembourg)
aStates["Macao"] = "|Coloane|Nossa Senhora de Fátima|Santo António|São Lázaro|São Lourenço|Sé|Taipa"; // 7 parishes (https://en.wikipedia.org/wiki/Parishes_of_Macau)
aStates["Madagascar"] = "|Alaotra-Mangoro|Ambatosoa|Amoron'i Mania|Analamanga|Analanjirofo|Androy|Anosy|Atsimo-Andrefana|Atsimo-Atsinanana|Atsinanana|Betsiboka|Boeny|Bongolava|Diana|Fitovinany|Haute Matsiatra|Ihorombe|Itasy|Melaky|Menabe|Sava|Sofia|Vakinankaratra|Vatovavy"; // 24 regions - verified 2025-01-16 via Wikipedia (includes new Ambatosoa 2025) (https://en.wikipedia.org/wiki/Regions_of_Madagascar)
aStates["Malawi"] = "|Balaka|Blantyre|Chikwawa|Chiradzulu|Chitipa|Dedza|Dowa|Karonga|Kasungu|Likoma|Lilongwe|Machinga|Mangochi|Mchinji|Mulanje|Mwanza|Mzimba|Neno|Nkhata Bay|Nkhotakota|Nsanje|Ntcheu|Ntchisi|Phalombe|Rumphi|Salima|Thyolo|Zomba"; // 28 districts - verified 2025-01-16 via Wikipedia (https://en.wikipedia.org/wiki/Districts_of_Malawi)
aStates["Malaysia"] = "|Johor|Kedah|Kelantan|Kuala Lumpur|Labuan|Melaka|Negeri Sembilan|Pahang|Perak|Perlis|Pulau Pinang|Putrajaya|Sabah|Sarawak|Selangor|Terengganu"; // 16 administrative divisions (13 states + 3 federal territories) (https://en.wikipedia.org/wiki/States_and_federal_territories_of_Malaysia)
aStates["Maldives"] = "|Addu|Alif Alif|Alif Dhaal|Baa|Dhaalu|Faafu|Fuvahmulah|Gaafu Alif|Gaafu Dhaalu|Gnaviyani|Haa Alif|Haa Dhaalu|Kaafu|Kulhudhuffushi|Laamu|Lhaviyani|Malé|Meemu|Noonu|Raa|Shaviyani|Thaa|Thinadhoo|Vaavu"; // 24 administrative divisions (20 atolls + 4 cities, Thinadhoo upgraded to city in 2023) (https://en.wikipedia.org/wiki/Administrative_divisions_of_the_Maldives)
aStates["Mali"] = "|Bamako|Bandiagara|Bougouni|Dioila|Douentza|Gao|Kayes|Kidal|Kita|Koulikoro|Koutiala|Ménaka|Mopti|Nara|Nioro|San|Ségou|Sikasso|Taoudénit|Tombouctou"; // 20 administrative divisions (19 regions + Bamako district, based on administrative reform of 2023) (https://fr.wikipedia.org/wiki/R%C3%A9gions_du_Mali)
aStates["Malta"] = "|Attard|Balzan|Birgu|Birkirkara|Birżebbuġa|Bormla|Dingli|Fgura|Floriana|Fontana|Għajnsielem|Għarb|Għargħur|Għasri|Għaxaq|Gudja|Gżira|Ħamrun|Iklin|Isla|Kalkara|Kerċem|Kirkop|Lija|Luqa|Marsa|Marsaskala|Marsaxlokk|Mdina|Mellieħa|Mġarr|Mosta|Mqabba|Msida|Mtarfa|Munxar|Nadur|Naxxar|Paola|Pembroke|Pietà|Qala|Qormi|Qrendi|Rabat Għawdex|Rabat Malta|Safi|San Ġiljan|San Ġwann|San Lawrenz|San Pawl il-Baħar|Sannat|Santa Luċija|Santa Venera|Siġġiewi|Sliema|Swieqi|Ta' Xbiex|Tarxien|Valletta|Xagħra|Xewkija|Xgħajra|Żabbar|Żebbuġ Għawdex|Żebbuġ Malta|Żejtun|Żurrieq"; // 68 local councils (https://en.wikipedia.org/wiki/Local_councils_of_Malta)
aStates["Marshall Islands"] = "|Ailinglaplap|Ailuk|Arno|Aur|Ebon|Enewetok|Jabat|Jaluit|Kili|Kwajalein|Lae|Lib|Likiep|Majuro|Maloelap|Mejit|Mili|Namorik|Namu|Rongelap|Ujae|Utirik|Wotho|Wotje"; // 24 inhabited municipalities (electoral districts) (https://en.wikipedia.org/wiki/List_of_islands_of_the_Marshall_Islands)
aStates["Martinique"] = "|Basse-Pointe|Bellefontaine|Case-Pilote|Ducos|Fonds-Saint-Denis|Fort-de-France|Grand'Rivière|Gros-Morne|L'Ajoupa-Bouillon|La Trinité|Le Carbet|Le Diamant|Le François|Le Lamentin|Le Lorrain|Le Marigot|Le Marin|Le Morne-Rouge|Le Morne-Vert|Le Prêcheur|Le Robert|Le Vauclin|Les Anses-d'Arlet|Les Trois-Îlets|Macouba|Rivière-Pilote|Rivière-Salée|Saint-Esprit|Saint-Joseph|Saint-Pierre|Sainte-Anne|Sainte-Luce|Sainte-Marie|Schoelcher"; // 34 communes (French overseas collectivity) (https://fr.wikipedia.org/wiki/Liste_des_communes_de_la_Martinique)
aStates["Mauritania"] = "|Adrar|Assaba|Brakna|Dakhlet Nouadhibou|Gorgol|Guidimaka|Hodh Ech Chargui|Hodh El Gharbi|Inchiri|Nouakchott-Nord|Nouakchott-Ouest|Nouakchott-Sud|Tagant|Tiris Zemmour|Trarza"; // 15 regions (wilayas) (https://fr.wikipedia.org/wiki/R%C3%A9gions_de_la_Mauritanie)
aStates["Mauritius"] = "|Flacq|Grand Port|Moka|Pamplemousses|Plaines Wilhems|Port Louis|Rivière du Rempart|Rivière Noire|Savanne"; // 9 districts of Mauritius Island (https://en.wikipedia.org/wiki/Districts_of_Mauritius)
aStates["Mayotte"] = "|Acoua|Bandraboua|Bandrele|Bouéni|Chiconi|Chirongui|Dembeni|Dzaoudzi|Kani-Kéli|Koungou|Mamoudzou|Mtsamboro|Ouangani|Pamandzi|Sada|Tsingoni"; // 16 communes (French overseas collectivity) (https://fr.wikipedia.org/wiki/Liste_des_communes_de_Mayotte)
aStates["Mexico"] = "|Aguascalientes|Baja California|Baja California Sur|Campeche|Chiapas|Chihuahua|Coahuila|Colima|Durango|Guanajuato|Guerrero|Hidalgo|Jalisco|México|Mexico City|Michoacán|Morelos|Nayarit|Nuevo León|Oaxaca|Puebla|Querétaro|Quintana Roo|San Luis Potosí|Sinaloa|Sonora|Tabasco|Tamaulipas|Tlaxcala|Veracruz|Yucatán|Zacatecas"; // 32 federal entities (31 states + Mexico City) (https://en.wikipedia.org/wiki/Administrative_divisions_of_Mexico)
aStates["Micronesia"] = "|Chuuk|Kosrae|Pohnpei|Yap"; // 4 states (https://en.wikipedia.org/wiki/Administrative_divisions_of_the_Federated_States_of_Micronesia)
aStates["Moldova"] = "|Anenii Noi|Bălți|Basarabeasca|Bender|Briceni|Cahul|Călărași|Cantemir|Căușeni|Chișinău|Cimișlia|Criuleni|Dondușeni|Drochia|Dubăsari|Edineț|Fălești|Florești|Gagauzia|Glodeni|Hîncești|Ialoveni|Leova|Nisporeni|Ocnița|Orhei|Rezina|Rîșcani|Sîngerei|Șoldănești|Soroca|Ștefan Vodă|Strășeni|Taraclia|Telenești|Transnistria|Ungheni"; // 37 administrative divisions (32 districts + 3 municipalities + 2 autonomous units) (https://en.wikipedia.org/wiki/Administrative_divisions_of_Moldova)
aStates["Monaco"] = "|Fontvieille|La Condamine|Monaco-Ville|Monte-Carlo"; // 4 quartiers (https://en.wikipedia.org/wiki/Administrative_divisions_of_Monaco)
aStates["Mongolia"] = "|Arkhangai|Bayan-Ölgii|Bayankhongor|Bulgan|Darkhan-Uul|Dornod|Dornogovi|Dundgobi|Govi-Altai|Govisümber|Khentii|Khovd|Khövsgöl|Ömnögovi|Orkhon|Övörkhangai|Selenge|Sükhbaatar|Töv|Ulaanbaatar|Uvs|Zavkhan"; // 22 administrative divisions (21 provinces + Ulaanbaatar capital municipality) (https://en.wikipedia.org/wiki/Provinces_of_Mongolia)
aStates["Montenegro"] = "|Andrijevica|Bar|Berane|Bijelo Polje|Cetinje|Danilovgrad|Gusinje|Herceg Novi|Kolašin|Kotor|Mojkovac|Nikšić|Petnjica|Plav|Pljevlja|Plužine|Podgorica|Rožaje|Šavnik|Tivat|Tuzi|Ulcinj|Žabljak|Zeta"; // 25 municipalities (https://en.wikipedia.org/wiki/Municipalities_of_Montenegro)
aStates["Montserrat"] = "|Saint Anthony|Saint Georges|Saint Peter's"; // 3 parishes (https://en.wikipedia.org/wiki/Administrative_divisions_of_Montserrat)
aStates["Morocco"] = "|Béni Mellal-Khénifra|Casablanca-Settat|Dakhla-Oued Ed-Dahab|Drâa-Tafilalet|Fès-Meknès|Guelmim-Oued Noun|L'Oriental|Laâyoune-Sakia El Hamra|Marrakech-Safi|Rabat-Salé-Kénitra|Souss-Massa|Tanger-Tetouan-Al Hoceima"; // 12 regions (since 2015) (https://en.wikipedia.org/wiki/Regions_of_Morocco)
aStates["Mozambique"] = "|Cabo Delgado|Gaza|Inhambane|Manica|Maputo|Maputo City|Nampula|Niassa|Sofala|Tete|Zambezia"; // 11 administrative divisions (10 provinces + Maputo City) (https://en.wikipedia.org/wiki/Provinces_of_Mozambique)
aStates["Myanmar"] = "|Ayeyarwady|Bago|Chin State|Kachin State|Kayah State|Kayin State|Magway|Mandalay|Mon State|Naypyitaw|Rakhine State|Sagaing|Shan State|Tanintharyi|Yangon"; // 15 administrative divisions (7 states + 7 regions + 1 union territory) (https://en.wikipedia.org/wiki/Administrative_divisions_of_Myanmar)
aStates["Namibia"] = "|ǡKaras|Erongo|Hardap|Kavango East|Kavango West|Khomas|Kunene|Ohangwena|Omaheke|Omusati|Oshana|Oshikoto|Otjozondjupa|Zambezi"; // 14 regions (updated 2025-01-16: Caprivi→Zambezi, Okavango split into East/West, added Unicode ǡKaras) (https://en.wikipedia.org/wiki/Regions_of_Namibia)
aStates["Nauru"] = "|Aiwo|Anabar|Anetan|Anibare|Baiti|Boe|Buada|Denigomodu|Ewa|Ijuw|Meneng|Nibok|Uaboe|Yaren"; // 14 districts (https://en.wikipedia.org/wiki/Administrative_divisions_of_Nauru)
aStates["Nepal"] = "|Bagmati|Gandaki|Karnali|Koshi|Lumbini|Madhesh|Sudurpashchim"; // 7 provinces (since 2015, replaced old 14 zones) (https://en.wikipedia.org/wiki/Provinces_of_Nepal)
aStates["Netherlands"] = "|Drenthe|Flevoland|Friesland|Gelderland|Groningen|Limburg|Noord-Brabant|Noord-Holland|Overijssel|Utrecht|Zeeland|Zuid-Holland"; // 12 provinces (https://en.wikipedia.org/wiki/Provinces_of_the_Netherlands)
aStates["New Caledonia"] = "|Iles Loyaute|Nord|Sud"; // 3 provinces (https://en.wikipedia.org/wiki/Administrative_divisions_of_New_Caledonia)
aStates["New Zealand"] = "|Akaroa|Amuri|Ashburton|Bay of Islands|Bruce|Buller|Chatham Islands|Cheviot|Clifton|Clutha|Cook|Dannevirke|Egmont|Eketahuna|Ellesmere|Eltham|Eyre|Featherston|Franklin|Golden Bay|Great Barrier Island|Grey|Hauraki Plains|Hawera|Hawke's Bay|Heathcote|Hikurangi|Hobson|Hokianga|Horowhenua|Hutt|Inangahua|Inglewood|Kaikoura|Kairanga|Kiwitea|Lake|Mackenzie|Malvern|Manaia|Manawatu|Mangonui|Maniototo|Marlborough|Masterton|Matamata|Mount Herbert|Ohinemuri|Opotiki|Oroua|Otamatea|Otorohanga|Oxford|Pahiatua|Paparua|Patea|Piako|Pohangina|Raglan|Rangiora|Rangitikei|Rodney|Rotorua|Runanga|Saint Kilda|Silverpeaks|Southland|Stewart Island|Stratford|Strathallan|Taranaki|Taumarunui|Taupo|Tauranga|Thames-Coromandel|Tuapeka|Vincent|Waiapu|Waiheke|Waihemo|Waikato|Waikohu|Waimairi|Waimarino|Waimate|Waimate West|Waimea|Waipa|Waipawa|Waipukurau|Wairarapa South|Wairewa|Wairoa|Waitaki|Waitomo|Waitotara|Wallace|Wanganui|Waverley|Westland|Whakatane|Whangarei|Whangaroa|Woodville"; // 16 regions (https://en.wikipedia.org/wiki/Regions_of_New_Zealand)
aStates["Nicaragua"] = "|Atlantico Norte|Atlantico Sur|Boaco|Carazo|Chinandega|Chontales|Esteli|Granada|Jinotega|Leon|Madriz|Managua|Masaya|Matagalpa|Nueva Segovia|Rio San Juan|Rivas"; // 17 administrative divisions (15 departments + 2 autonomous regions) (https://en.wikipedia.org/wiki/Departments_of_Nicaragua)
aStates["Niger"] = "|Agadez|Diffa|Dosso|Maradi|Niamey|Tahoua|Tillaberi|Zinder"; // 8 administrative divisions (7 regions + Niamey capital district) (https://en.wikipedia.org/wiki/Regions_of_Niger)
aStates["Nigeria"] = "|Abia|Abuja Federal Capital Territory|Adamawa|Akwa Ibom|Anambra|Bauchi|Bayelsa|Benue|Borno|Cross River|Delta|Ebonyi|Edo|Ekiti|Enugu|Gombe|Imo|Jigawa|Kaduna|Kano|Katsina|Kebbi|Kogi|Kwara|Lagos|Nassarawa|Niger|Ogun|Ondo|Osun|Oyo|Plateau|Rivers|Sokoto|Taraba|Yobe|Zamfara"; // 37 administrative divisions (36 states + Federal Capital Territory) (https://en.wikipedia.org/wiki/States_of_Nigeria)
aStates["Niue"] = "|Alofi North|Alofi South|Avatele|Hakupu|Hikutavake|Lakepa|Liku|Makefu|Mutalau|Namukulu|Tamakautoga|Toi|Tuapa|Vaiea"; // 14 villages (https://en.wikipedia.org/wiki/Villages_of_Niue)
aStates["Norfolk Island"] = "|Anson Bay|Ball Bay|Bumboras|Burnt Pine|Cascade|Crystal Pool|Duncombe Bay|Kingston|Longridge|Rocky Point"; // 10 localities (https://en.wikipedia.org/wiki/Administrative_divisions_of_Norfolk_Island)
aStates["North Macedonia"] = "|Eastern|Northeastern|Pelagonia|Polog|Skopje|Southeastern|Southwestern|Vardar"; // 8 statistical regions (https://en.wikipedia.org/wiki/Statistical_regions_of_North_Macedonia)
aStates["Northern Mariana Islands"] = "|Northern Islands|Rota|Saipan|Tinian"; // 4 municipalities (https://en.wikipedia.org/wiki/Administrative_divisions_of_the_Northern_Mariana_Islands)
aStates["Norway"] = "|Agder|Akershus|Buskerud|Finnmark|Innlandet|Møre og Romsdal|Nordland|Oslo|Østfold|Rogaland|Telemark|Troms|Trøndelag|Vestfold|Vestland"; // 15 counties (since 2024, updated from 19 pre-2020 structure) (https://en.wikipedia.org/wiki/Counties_of_Norway)
aStates["Oman"] = "|Ad Dakhiliyah|Ad Dhahirah|Al Batinah North|Al Batinah South|Al Buraimi|Al Wusta|Ash Sharqiyah North|Ash Sharqiyah South|Dhofar|Musandam|Muscat"; // 11 governorates (since 2011) - Al Batinah/Ash Sharqiyah split, Al Buraimi added, updated Arabic names (https://en.wikipedia.org/wiki/Governorates_of_Oman)
aStates["Pakistan"] = "|Azad Kashmir|Balochistan|Gilgit-Baltistan|Islamabad Capital Territory|Khyber Pakhtunkhwa|Punjab|Sindh"; // 7 administrative units (4 provinces + 1 federal territory + 2 autonomous territories) - FATA merged into KPK (2018), NWFP renamed (2010) (https://en.wikipedia.org/wiki/Administrative_units_of_Pakistan)
aStates["Palau"] = "|Aimeliik|Airai|Angaur|Hatohobei|Kayangel|Koror|Melekeok|Ngaraard|Ngarchelong|Ngardmau|Ngatpang|Ngchesar|Ngeremlengui|Ngiwal|Peleliu|Sonsorol"; // Updated: 16 states (removed "Palau Island", "Sonsoral", "Tobi" - Sonsorol includes Sonsoral and Tobi islands, Hatohobei includes Hatohobei) (https://en.wikipedia.org/wiki/States_of_Palau)
aStates["Palestine"] = "|Gaza Strip|West Bank"; // 2 territories under Palestinian Authority (Gaza Strip and West Bank) (https://en.wikipedia.org/wiki/Palestinian_territories)
aStates["Panama"] = "|Bocas del Toro|Chiriqui|Cocle|Colon|Darien|Emberá-Wounaan|Guna Yala|Herrera|Los Santos|Ngäbe-Buglé|Panama|Panamá Oeste|Veraguas"; // Updated: San Blas renamed to Guna Yala, added Panamá Oeste province (2014), added major comarcas (https://en.wikipedia.org/wiki/Provinces_of_Panama)
aStates["Papua New Guinea"] = "|Bougainville|Central|Chimbu|East New Britain|East Sepik|Eastern Highlands|Enga|Gulf|Hela|Jiwaka|Madang|Manus|Milne Bay|Morobe|National Capital|New Ireland|Northern|Sandaun|Southern Highlands|West New Britain|Western|Western Highlands"; // 22 provinces (https://en.wikipedia.org/wiki/Provinces_of_Papua_New_Guinea)
aStates["Paraguay"] = "|Alto Paraguay|Alto Parana|Amambay|Asuncion (city)|Boqueron|Caaguazu|Caazapa|Canindeyu|Central|Concepcion|Cordillera|Guaira|Itapua|Misiones|Neembucu|Paraguari|Presidente Hayes|San Pedro"; // 18 administrative units (17 departments + 1 capital district) (https://en.wikipedia.org/wiki/Departments_of_Paraguay)
aStates["Peru"] = "|Amazonas|Ancash|Apurimac|Arequipa|Ayacucho|Cajamarca|Callao|Cusco|Huancavelica|Huanuco|Ica|Junin|La Libertad|Lambayeque|Lima|Loreto|Madre de Dios|Moquegua|Pasco|Piura|Puno|San Martin|Tacna|Tumbes|Ucayali"; // 25 regions (24 departments/regions + 1 constitutional province Callao) (https://en.wikipedia.org/wiki/Regions_of_Peru)
aStates["Philippines"] = "|Abra|Agusan del Norte|Agusan del Sur|Aklan|Albay|Antique|Apayao|Aurora|Basilan|Bataan|Batanes|Batangas|Benguet|Biliran|Bohol|Bukidnon|Bulacan|Cagayan|Camarines Norte|Camarines Sur|Camiguin|Capiz|Catanduanes|Cavite|Cebu|Cotabato|Davao de Oro|Davao del Norte|Davao del Sur|Davao Occidental|Davao Oriental|Dinagat Islands|Eastern Samar|Guimaras|Ifugao|Ilocos Norte|Ilocos Sur|Iloilo|Isabela|Kalinga|La Union|Laguna|Lanao del Norte|Lanao del Sur|Leyte|Maguindanao del Norte|Maguindanao del Sur|Marinduque|Masbate|Misamis Occidental|Misamis Oriental|Mountain Province|Negros Occidental|Negros Oriental|Northern Samar|Nueva Ecija|Nueva Vizcaya|Occidental Mindoro|Oriental Mindoro|Palawan|Pampanga|Pangasinan|Quezon|Quirino|Rizal|Romblon|Samar|Sarangani|Siquijor|Sorsogon|South Cotabato|Southern Leyte|Sultan Kudarat|Sulu|Surigao del Norte|Surigao del Sur|Tarlac|Tawi-Tawi|Western Samar|Zambales|Zamboanga del Norte|Zamboanga del Sur|Zamboanga Sibugay"; // 82 provinces (corrected from problematic mix of provinces/cities to official provinces only per Wikipedia 2022) (https://en.wikipedia.org/wiki/Provinces_of_the_Philippines)
aStates["Pitcairn Islands"] = "|Adamstown|Bounty Bay|Down Rope|Kingston|Pulau|St. Paul's|Tedside|The Edge"; // 8 small settlements on Pitcairn Island (https://en.wikipedia.org/wiki/Pitcairn_Islands)
aStates["Poland"] = "|Dolnoslaskie|Kujawsko-Pomorskie|Lodzkie|Lubelskie|Lubuskie|Malopolskie|Mazowieckie|Opolskie|Podkarpackie|Podlaskie|Pomorskie|Slaskie|Swietokrzyskie|Warminsko-Mazurskie|Wielkopolskie|Zachodniopomorskie"; // 16 voivodeships (administrative regions) (https://en.wikipedia.org/wiki/Voivodeships_of_Poland)
aStates["Portugal"] = "|Acores (Azores)|Aveiro|Beja|Braga|Braganca|Castelo Branco|Coimbra|Evora|Faro|Guarda|Leiria|Lisboa|Madeira|Portalegre|Porto|Santarem|Setubal|Viana do Castelo|Vila Real|Viseu"; // 20 administrative units (18 districts + 2 autonomous regions) (https://en.wikipedia.org/wiki/Districts_of_Portugal)
aStates["Puerto Rico"] = "|Adjuntas|Aguada|Aguadilla|Aguas Buenas|Aibonito|Anasco|Arecibo|Arroyo|Barceloneta|Barranquitas|Bayamon|Cabo Rojo|Caguas|Camuy|Canovanas|Carolina|Catano|Cayey|Ceiba|Ciales|Cidra|Coamo|Comerio|Corozal|Culebra|Dorado|Fajardo|Florida|Guanica|Guayama|Guayanilla|Guaynabo|Gurabo|Hatillo|Hormigueros|Humacao|Isabela|Jayuya|Juana Diaz|Juncos|Lajas|Lares|Las Marias|Las Piedras|Loiza|Luquillo|Manati|Maricao|Maunabo|Mayaguez|Moca|Morovis|Naguabo|Naranjito|Orocovis|Patillas|Penuelas|Ponce|Quebradillas|Rincon|Rio Grande|Sabana Grande|Salinas|San German|San Juan|San Lorenzo|San Sebastian|Santa Isabel|Toa Alta|Toa Baja|Trujillo Alto|Utuado|Vega Alta|Vega Baja|Vieques|Villalba|Yabucoa|Yauco"; // 78 municipalities (complete list of Puerto Rican municipios) (https://en.wikipedia.org/wiki/List_of_municipalities_of_Puerto_Rico)
aStates["Qatar"] = "|Ad Dawhah|Al Daayen|Al Khor|Al Rayyan|Al Shamal|Al Wakrah|Al-Shahaniya|Umm Salal"; // Updated: 8 current municipalities (removed Al Ghuwayriyah, Al Jumayliyah, Jarayan al Batinah - merged in 2004; added Al Daayen 2004, Al-Shahaniya 2014) (https://en.wikipedia.org/wiki/Municipalities_of_Qatar)
aStates["Réunion"] = "|Bras-Panon|Cilaos|Entre-Deux|L'Étang-Salé|La Plaine-des-Palmistes|La Possession|Le Port|Le Tampon|Les Avirons|Les Trois-Bassins|Petite-Île|Saint-André|Saint-Benoît|Saint-Denis|Saint-Joseph|Saint-Leu|Saint-Louis|Saint-Paul|Saint-Philippe|Saint-Pierre|Sainte-Marie|Sainte-Rose|Sainte-Suzanne|Salazie"; // 24 communes (French overseas department) (https://en.wikipedia.org/wiki/Administrative_divisions_of_R%C3%A9union)
aStates["Romania"] = "|Alba|Arad|Argeș|Bacău|Bihor|Bistrița-Năsăud|Botoșani|Brăila|Brașov|Bucharest|Buzău|Călărași|Caraș-Severin|Cluj|Constanța|Covasna|Dâmbovița|Dolj|Galați|Giurgiu|Gorj|Harghita|Hunedoara|Ialomița|Iași|Ilfov|Maramureș|Mehedinți|Mureș|Neamț|Olt|Prahova|Sălaj|Satu Mare|Sibiu|Suceava|Teleorman|Timiș|Tulcea|Vâlcea|Vaslui|Vrancea"; // 41 counties (40 județe + Bucharest municipality with correct diacritical marks) (https://en.wikipedia.org/wiki/Counties_of_Romania)
aStates["Russian Federation"] = "|Adygea (Maykop)|Altai Krai (Barnaul)|Altai Republic (Gorno-Altaysk)|Amur Oblast (Blagoveshchensk)|Arkhangelsk Oblast (Arkhangelsk)|Astrakhan Oblast (Astrakhan)|Bashkortostan (Ufa)|Belgorod Oblast (Belgorod)|Bryansk Oblast (Bryansk)|Buryatia (Ulan-Ude)|Chechnya (Grozny)|Chelyabinsk Oblast (Chelyabinsk)|Chukotka Autonomous Okrug (Anadyr)|Chuvashia (Cheboksary)|Dagestan (Makhachkala)|Ingushetia (Magas)|Irkutsk Oblast (Irkutsk)|Ivanovo Oblast (Ivanovo)|Jewish Autonomous Oblast (Birobidzhan)|Kabardino-Balkaria (Nalchik)|Kaliningrad Oblast (Kaliningrad)|Kalmykia (Elista)|Kaluga Oblast (Kaluga)|Kamchatka Krai (Petropavlovsk-Kamchatsky)|Karachay-Cherkessia (Cherkessk)|Karelia (Petrozavodsk)|Kemerovo Oblast (Kemerovo)|Khabarovsk Krai (Khabarovsk)|Khakassia (Abakan)|Khanty-Mansi Autonomous Okrug (Khanty-Mansiysk)|Kirov Oblast (Kirov)|Komi Republic (Syktyvkar)|Kostroma Oblast (Kostroma)|Krasnodar Krai (Krasnodar)|Krasnoyarsk Krai (Krasnoyarsk)|Kurgan Oblast (Kurgan)|Kursk Oblast (Kursk)|Leningrad Oblast (Gatchina)|Lipetsk Oblast (Lipetsk)|Magadan Oblast (Magadan)|Mari El (Yoshkar-Ola)|Mordovia (Saransk)|Moscow (Moscow)|Moscow Oblast (Balashikha)|Murmansk Oblast (Murmansk)|Nenets Autonomous Okrug (Naryan-Mar)|Nizhny Novgorod Oblast (Nizhny Novgorod)|North Ossetia–Alania (Vladikavkaz)|Novgorod Oblast (Veliky Novgorod)|Novosibirsk Oblast (Novosibirsk)|Omsk Oblast (Omsk)|Orenburg Oblast (Orenburg)|Oryol Oblast (Oryol)|Penza Oblast (Penza)|Perm Krai (Perm)|Primorsky Krai (Vladivostok)|Pskov Oblast (Pskov)|Rostov Oblast (Rostov-on-Don)|Ryazan Oblast (Ryazan)|Saint Petersburg (Saint Petersburg)|Sakha (Yakutsk)|Sakhalin Oblast (Yuzhno-Sakhalinsk)|Samara Oblast (Samara)|Saratov Oblast (Saratov)|Smolensk Oblast (Smolensk)|Stavropol Krai (Stavropol)|Sverdlovsk Oblast (Yekaterinburg)|Tambov Oblast (Tambov)|Tatarstan (Kazan)|Tomsk Oblast (Tomsk)|Tula Oblast (Tula)|Tuva (Kyzyl)|Tver Oblast (Tver)|Tyumen Oblast (Tyumen)|Udmurtia (Izhevsk)|Ulyanovsk Oblast (Ulyanovsk)|Vladimir Oblast (Vladimir)|Volgograd Oblast (Volgograd)|Vologda Oblast (Vologda)|Voronezh Oblast (Voronezh)|Yamalo-Nenets Autonomous Okrug (Salekhard)|Yaroslavl Oblast (Yaroslavl)|Zabaykalsky Krai (Chita)"; // 83 federal subjects: 21 republics, 9 krais, 46 oblasts, 2 federal cities, 1 autonomous oblast, 4 autonomous okrugs (merged autonomous okrugs from 2005-2008 reforms included) (https://en.wikipedia.org/wiki/Federal_subjects_of_Russia)
aStates["Rwanda"] = "|Eastern|Kigali|Northern|Southern|Western"; // 5 current provinces since 2006 administrative reform (replaced 12 old provinces/prefectures) (https://en.wikipedia.org/wiki/Provinces_of_Rwanda)
aStates["Saint Barthélemy"] = "|Anse des Cayes|Anse des Lézards|Anse du Gouverneur|Barrière des Quatres Vents|Camaruche|Carénage|Col de la Tourmente|Colombier|Corossol|Devet|Flamands|Grand Cul-de-Sac|Grand Fond|Grande Saline|Grande Vigie|Gustavia|Gustavia|La Grande Montagne|La Pointe|Le Château|Le Palidor|Lorient|Lurin|Merlette|Morne Criquet|Morne de Dépoudré|Morne Rouge|Petite Saline|Pointe Milou|Public|Quartier du Roi|Saint-Jean|Terre Neuve|Toiny|Vitet"; // Overseas collectivity of France - 40 quartiers grouped into 2 paroisses (parishes). Capital: Gustavia (https://en.wikipedia.org/wiki/Saint_Barth%C3%A9lemy)
aStates["Saint Helena, Ascension and Tristan da Cunha"] = "|Alarm Forest|Blue Hill|Edinburgh of the Seven Seas|Georgetown|Half Tree Hollow|Jamestown|Longwood|Saint Paul's|Sandy Bay"; // British Overseas Territory - Saint Helena (8 districts), Ascension Island (Georgetown), Tristan da Cunha (Edinburgh of the Seven Seas). Capital: Jamestown (https://en.wikipedia.org/wiki/Saint_Helena)
aStates["Saint Kitts and Nevis"] = "|Basseterre|Christ Church Nichola Town|Saint Anne Sandy Point|Saint George Basseterre|Saint George Gingerland|Saint James Windward|Saint John Capisterre|Saint John Figtree|Saint Mary Cayon|Saint Paul Capisterre|Saint Paul Charlestown|Saint Peter Basseterre|Saint Thomas Lowland|Saint Thomas Middle Island|Trinity Palmetto Point"; // Federation - 14 parishes: 9 on Saint Kitts, 5 on Nevis. Capital: Basseterre (https://en.wikipedia.org/wiki/Saint_Kitts_and_Nevis)
aStates["Saint Lucia"] = "|Anse la Raye|Canaries|Castries|Choiseul|Dennery|Gros Islet|Laborie|Micoud|Soufrière|Vieux Fort"; // 10 districts. Capital: Castries. Former quarters Dauphin and Praslin were merged into Gros Islet and Micoud respectively (https://en.wikipedia.org/wiki/Saint_Lucia)
aStates["Saint Martin"] = "|Cole Bay|Cul de Sac|Grand-Case|Little Bay|Lower Prince's Quarter|Lowlands|Marigot|Philipsburg|Quartier-d'Orleans|Simpson Bay|Upper Prince's Quarter"; // Divided island: French Saint Martin (capital Marigot) and Dutch Sint Maarten (capital Philipsburg). Treaty of Concordia 1648 (https://en.wikipedia.org/wiki/Saint_Martin)
aStates["Saint Pierre and Miquelon"] = "|Isle-aux-Marins (former commune until 1945)|Langlade|Miquelon|Miquelon-Langlade|Saint-Pierre|Saint-Pierre (capital)"; // French overseas collectivity: 2 communes (Saint-Pierre and Miquelon-Langlade) (https://en.wikipedia.org/wiki/Saint_Pierre_and_Miquelon)
aStates["Saint Vincent and the Grenadines"] = "|Barrouallie|Charlotte Parish|Chateaubelair|Georgetown|Grenadines Parish|Kingstown (capital)|Layou|Port Elizabeth|Saint Andrew Parish|Saint David Parish|Saint George Parish|Saint Patrick Parish"; // 6 parishes + 1 dependency (Grenadines) (https://en.wikipedia.org/wiki/Saint_Vincent_and_the_Grenadines)
aStates["Samoa"] = "|A'ana|Afega|Aiga-i-le-Tai|Apia (capital)|Asau|Atua|Fa'asaleleaga|Gaga'emauga|Gagaifomauga|Leulumoega|Lufilufi|Mulifanua|Palauli|Safotu|Safotulafai|Saleaula|Samamea|Satupa'itea|Tuamasaga|Va'a-o-Fonoti|Vailoa|Vaisigano"; // 11 traditional districts + capital (https://en.wikipedia.org/wiki/Samoa)
aStates["San Marino"] = "|Acquaviva|Borgo Maggiore|Chiesanuova|City of San Marino|Domagnano|Faetano|Fiorentino|Montegiardino|Serravalle"; // 9 castelli (https://en.wikipedia.org/wiki/San_Marino)
aStates["São Tomé and Príncipe"] = "|Água Grande|Cantagalo|Caué|Lembá|Lobata|Mé-Zóchi|Pagué"; // 7 districts (https://en.wikipedia.org/wiki/S%C3%A3o_Tom%C3%A9_and_Pr%C3%ADncipe)
aStates["Saudi Arabia"] = "|'Asir|Al-Baha|Al-Jawf|Al-Qassim|Eastern Province|Hail|Jazan|Mecca|Medina|Najran|Northern Borders|Riyadh|Tabuk"; // 13 provinces (administrative regions) (https://en.wikipedia.org/wiki/Provinces_of_Saudi_Arabia)
aStates["Senegal"] = "|Dakar|Diourbel|Fatick|Kaffrine|Kaolack|Kédougou|Kolda|Louga|Matam|Saint-Louis|Sédhiou|Tambacounda|Thiès|Ziguinchor"; // 14 regions (https://en.wikipedia.org/wiki/Regions_of_Senegal)
aStates["Serbia"] = "|Belgrade|Bor|Braničevo|Kolubara|Mačva|Moravički|Nišava|North Bačka|North Banat|Pčinja|Peć|Pirot|Podunavlje|Pomoravlje|Rasina|Raška|South Bačka|South Banat|Srem|Šumadija|Toplica|West Bačka|Zaječar|Zlatibor"; // 24 districts (https://en.wikipedia.org/wiki/Serbia)
aStates["Seychelles"] = "|Anse aux Pins|Anse Boileau|Anse Etoile|Anse Royale|Au Cap|Baie Lazare|Baie Sainte Anne|Beau Vallon|Bel Air|Bel Ombre|Cascade|English River|Glacis|Grand'Anse Mahe|Grand'Anse Praslin|La Digue and Inner Islands|Les Mamelles|Mont Buxton|Mont Fleuri|Outer Islands|Plaisance|Pointe La Rue|Port Glaud|Roche Caiman|Saint Louis|Takamaka"; // 26 administrative districts (https://en.wikipedia.org/wiki/Seychelles)
aStates["Sierra Leone"] = "|Bo|Bombali|Bonthe|Falaba|Kailahun|Kambia|Karene|Kenema|Koinadugu|Kono|Moyamba|Port Loko|Pujehun|Tonkolili|Western Area Rural|Western Area Urban"; // 14 districts (https://en.wikipedia.org/wiki/Sierra_Leone)
aStates["Singapore"] = "|Central Region|East Region|North Region|North-East Region|West Region"; // 5 planning regions (https://en.wikipedia.org/wiki/Regions_of_Singapore)
aStates["Sint Maarten"] = "|Cole Bay|Cul de Sac|Little Bay|Lower Prince's Quarter|Lowlands|Philipsburg|Simpson Bay|Upper Prince's Quarter"; // 8 districts (Dutch part of Saint Martin island) (https://en.wikipedia.org/wiki/Sint_Maarten)
aStates["Slovakia"] = "|Banskobystricky|Bratislavsky|Kosicky|Nitriansky|Presovsky|Trenciansky|Trnavsky|Zilinsky"; // 8 regions (kraje) (https://en.wikipedia.org/wiki/Regions_of_Slovakia)
aStates["Slovenia"] = "|gorenjska|goriška|jugovzhodna Slovenija|koroška|obalno-kraška|osrednjeslovenska|podravska|pomurska|posavska|primorsko-notranjska|savinjska|zasavska"; // 12 statistical regions (https://en.wikipedia.org/wiki/Statistical_regions_of_Slovenia)
aStates["Solomon Islands"] = "|Central|Choiseul|Guadalcanal|Isabel|Makira-Ulawa|Malaita|Rennell and Bellona|Temotu|Western"; // 9 provinces (https://en.wikipedia.org/wiki/Provinces_of_Solomon_Islands)
aStates["Somalia"] = "|Awdal|Bakool|Banaadir|Bari|Bay|Galguduud|Gedo|Hiiraan|Jubbada Dhexe|Jubbada Hoose|Mudug|Nugaal|Sanaag|Shabeellaha Dhexe|Shabeellaha Hoose|Sool|Togdheer|Woqooyi Galbeed"; // 18 regions (https://en.wikipedia.org/wiki/Administrative_divisions_of_Somalia)
aStates["South Africa"] = "|Eastern Cape|Free State|Gauteng|KwaZulu-Natal|Limpopo|Mpumalanga|North West|Northern Cape|Western Cape"; // 9 provinces (https://en.wikipedia.org/wiki/Provinces_of_South_Africa)
aStates["South Georgia and South Sandwich Islands"] = "|South Georgia|South Sandwich Islands"; // No permanent population, no administrative divisions - consists of South Georgia and the South Sandwich Islands (https://en.wikipedia.org/wiki/South_Georgia_and_the_South_Sandwich_Islands)
aStates["South Sudan"] = "|Abyei Area|Central Equatoria|Eastern Equatoria|Greater Pibor Area|Jonglei|Lakes|Northern Bahr el Ghazal|Ruweng Area|Unity|Upper Nile|Warrap|Western Bahr el Ghazal|Western Equatoria"; // 10 states and 3 administrative areas (https://en.wikipedia.org/wiki/States_of_South_Sudan)
aStates["Spain"] = "|Andalucía|Aragón|Asturias|Cantabria|Castilla y León|Castilla-La Mancha|Cataluña|Ceuta|Comunidad Valenciana|Extremadura|Galicia|Islas Baleares|Islas Canarias|La Rioja|Madrid|Melilla|Murcia|Navarra|País Vasco"; // 17 autonomous communities + 2 autonomous cities (https://en.wikipedia.org/wiki/Autonomous_communities_of_Spain)
aStates["Sri Lanka"] = "|Central|Eastern|North Central|North Western|Northern|Sabaragamuwa|Southern|Uva|Western"; // 9 provinces (https://en.wikipedia.org/wiki/Provinces_of_Sri_Lanka)
aStates["Sudan"] = "|Al Qadarif|Blue Nile|Central Darfur|East Darfur|Gezira|Kassala|Khartoum|North Darfur|North Kordofan|Northern|Red Sea|River Nile|Sennar|South Darfur|South Kordofan|West Darfur|West Kordofan|White Nile"; // 18 states (https://en.wikipedia.org/wiki/States_of_Sudan)
aStates["Suriname"] = "|Brokopondo|Commewijne|Coronie|Marowijne|Nickerie|Para|Paramaribo|Saramacca|Sipaliwini|Wanica"; // 10 districts (https://en.wikipedia.org/wiki/Districts_of_Suriname)
aStates["Svalbard and Jan Mayen"] = "|Barentsøya|Bjørnøya|Edgeøya|Hopen|Jan Mayen|Nordaustlandet|Prins Karls Forland|Spitsbergen"; // Norwegian territories: Svalbard (unincorporated area, no administrative divisions - main islands listed) and Jan Mayen (uninhabited island) (https://en.wikipedia.org/wiki/Svalbard)
aStates["Sweden"] = "|Blekinge|Dalarna|Gävleborg|Gotland|Halland|Jämtland|Jönköping|Kalmar|Kronoberg|Norrbotten|Örebro|Östergötland|Skåne|Södermanland|Stockholm|Uppsala|Värmland|Västerbotten|Västernorrland|Västmanland|Västra Götaland"; // 21 counties (län) (https://en.wikipedia.org/wiki/Counties_of_Sweden)
aStates["Switzerland"] = "|Aargau|Appenzell Ausserrhoden|Appenzell Innerrhoden|Basel-Landschaft|Basel-Stadt|Bern|Freiburg|Genève|Glarus|Graubünden|Jura|Luzern|Neuenburg|Nidwalden|Obwalden|Schaffhausen|Schwyz|Solothurn|St. Gallen|Thurgau|Ticino|Uri|Valais|Vaud|Zug|Zürich"; // 26 cantons (https://en.wikipedia.org/wiki/Cantons_of_Switzerland)
aStates["Syria"] = "|Al-Hasakah|Aleppo|Damascus|Daraa|Deir ez-Zor|Hama|Homs|Idlib|Latakia|Quneitra|Raqqa|Rif Dimashq|Suwayda|Tartus"; // 14 governorates (https://en.wikipedia.org/wiki/Governorates_of_Syria)
aStates["Taiwan"] = "|Changhua|Chiayi|Chiayi (City)|Hsinchu|Hsinchu (City)|Hualien|Kaohsiung|Keelung|Kinmen|Lienchiang|Miaoli|Nantou|New Taipei|Penghu|Pingtung|Taichung|Tainan|Taipei|Taitung|Taoyuan|Yilan|Yunlin"; // 22 administrative divisions (13 counties, 6 special municipalities, 3 cities) (https://en.wikipedia.org/wiki/Administrative_divisions_of_Taiwan)
aStates["Tajikistan"] = "|Dushanbe|Khatlon|Kuhistoni Badakhshon|Nohiyahoi Tobei Jumhurí|Sughd"; // 5 administrative divisions (https://en.wikipedia.org/wiki/Regions_of_Tajikistan)
aStates["Tanzania"] = "|Arusha|Dar es Salaam|Dodoma|Geita|Iringa|Kagera|Kaskazini Pemba|Kaskazini Unguja|Katavi|Kigoma|Kilimanjaro|Kusini Pemba|Kusini Unguja|Lindi|Manyara|Mara|Mbeya|Mjini Magharibi|Morogoro|Mtwara|Mwanza|Njombe|Pwani|Rukwa|Ruvuma|Shinyanga|Simiyu|Singida|Songwe|Tabora|Tanga"; // 31 regions (https://en.wikipedia.org/wiki/Regions_of_Tanzania)
aStates["Thailand"] = "|Amnat Charoen|Ang Thong|Bueng Kan|Buriram|Chachoengsao|Chai Nat|Chaiyaphum|Chanthaburi|Chiang Mai|Chiang Rai|Chonburi|Chumphon|Kalasin|Kamphaeng Phet|Kanchanaburi|Khon Kaen|Krabi|Krung Thep Maha Nakhon (Bangkok)|Lampang|Lamphun|Loei|Lopburi|Mae Hong Son|Maha Sarakham|Mukdahan|Nakhon Nayok|Nakhon Pathom|Nakhon Phanom|Nakhon Ratchasima|Nakhon Sawan|Nakhon Si Thammarat|Nan|Narathiwat|Nong Bua Lamphu|Nong Khai|Nonthaburi|Pathum Thani|Pattani|Phang Nga|Phatthalung|Phayao|Phetchabun|Phetchaburi|Phichit|Phitsanulok|Phrae|Phuket|Prachinburi|Prachuap Khiri Khan|Ranong|Ratchaburi|Rayong|Roi Et|Sa Kaeo|Sakon Nakhon|Samut Prakan|Samut Sakhon|Samut Songkhram|Sara Buri|Satun|Sing Buri|Sisaket|Songkhla|Sukhothai|Suphan Buri|Surat Thani|Surin|Tak|Trang|Trat|Ubon Ratchathani|Udon Thani|Uthai Thani|Uttaradit|Yala|Yasothon"; // 76 provinces and special administrative areas (https://en.wikipedia.org/wiki/Provinces_of_Thailand)
aStates["Timor-Leste"] = "|Aileu|Ainaro|Atauro|Baucau|Bobonaro|Covalima|Dili|Ermera|Lautem|Liquica|Manatuto|Manufahi|Oecusse|Viqueque"; // 14 administrative posts and municipalities (https://en.wikipedia.org/wiki/Administrative_divisions_of_Timor-Leste)
aStates["Togo"] = "|Centrale|Kara|Maritime|Plateaux|Savanes"; // 5 regions (https://en.wikipedia.org/wiki/Regions_of_Togo)
aStates["Tokelau"] = "|Atafu|Fakaofo|Nukunonu"; // 3 atolls (https://en.wikipedia.org/wiki/Administrative_divisions_of_Tokelau)
aStates["Tonga"] = "|Haʻapai|ʻEua|Niuas|Tongatapu|Vavaʻu"; // 5 administrative divisions (https://en.wikipedia.org/wiki/Administrative_divisions_of_Tonga)
aStates["Trinidad and Tobago"] = "|Arima|Chaguanas|Couva-Tabaquite-Talparo|Diego Martin|Mayaro-Rio Claro|Penal-Debe|Point Fortin|Port of Spain|Princes Town|Rio Claro-Mayaro|San Fernando|San Juan-Laventille|Sangre Grande|Siparia|Tunapuna-Piarco"; // 15 regional corporations and boroughs (https://en.wikipedia.org/wiki/Administrative_divisions_of_Trinidad_and_Tobago)
aStates["Tunisia"] = "|Ariana|Béja|Ben Arous|Bizerte|Gabès|Gafsa|Jendouba|Kairouan|Kasserine|Kebili|Kef|Mahdia|Manouba|Medenine|Monastir|Nabeul|Sfax|Sidi Bouzid|Siliana|Sousse|Tataouine|Tozeur|Tunis|Zaghouan"; // 24 governorates (https://en.wikipedia.org/wiki/Governorates_of_Tunisia)
aStates["Türkiye"] = "|Adana|Adıyaman|Afyonkarahisar|Ağrı|Aksaray|Amasya|Ankara|Antalya|Ardahan|Artvin|Aydın|Balıkesir|Bartın|Batman|Bayburt|Bilecik|Bingöl|Bitlis|Bolu|Burdur|Bursa|Çanakkale|Çankırı|Çorum|Denizli|Diyarbakır|Düzce|Edirne|Elazığ|Erzincan|Erzurum|Eskişehir|Gaziantep|Giresun|Gümüşhane|Hakkâri|Hatay|Iğdır|Isparta|İstanbul|İzmir|Kahramanmaraş|Karabük|Karaman|Kars|Kastamonu|Kayseri|Kilis|Kırıkkale|Kırklareli|Kırşehir|Kocaeli|Konya|Kütahya|Malatya|Manisa|Mardin|Mersin|Muğla|Muş|Nevşehir|Niğde|Ordu|Osmaniye|Rize|Sakarya|Samsun|Şanlıurfa|Siirt|Sinop|Sivas|Şırnak|Tekirdağ|Tokat|Trabzon|Tunceli|Uşak|Van|Yalova|Yozgat|Zonguldak"; // 81 provinces (https://en.wikipedia.org/wiki/Provinces_of_Turkey)
aStates["Turkmenistan"] = "|Ahal|Ashgabat|Balkan|Daşoguz|Lebap|Mary"; // 6 regions including the capital city (https://en.wikipedia.org/wiki/Administrative_divisions_of_Turkmenistan)
aStates["Turks and Caicos Islands"] = "|Grand Turk|Middle Caicos|North Caicos|Providenciales|Salt Cay|South Caicos"; // 6 main islands (https://en.wikipedia.org/wiki/Administrative_divisions_of_the_Turks_and_Caicos_Islands)
aStates["Tuvalu"] = "|Funafuti|Nanumaga|Nanumea|Niulakita|Niutao|Nui|Nukufetau|Nukulaelae|Vaitupu"; // 9 islands (https://en.wikipedia.org/wiki/Administrative_divisions_of_Tuvalu)
aStates["Uganda"] = "|Abim|Adjumani|Agago|Alebtong|Amolatar|Amudat|Amuria|Amuru|Apac|Arua|Budaka|Bududa|Bugiri|Bugweri|Buhweju|Buikwe|Bukedea|Bukomansimbi|Bukwo|Bukwo|Bulambuli|Buliisa|Bundibugyo|Bunyangabu|Bushenyi|Busia|Butaleja|Butambala|Butebo|Buvuma|Buyende|Dokolo|Gomba|Gulu|Hoima|Ibanda|Iganga|Isingiro|Jinja|Kaabong|Kabale|Kabarole|Kaberamaido|Kagadi|Kakumiro|Kalaki|Kalangala|Kaliro|Kalungu|Kampala|Kamuli|Kamwenge|Kanungu|Kapchorwa|Kapelebyong|Karenga|Kasanda|Kasese|Katakwi|Kayunga|Kazo|Kibaale|Kiboga|Kibuku|Kikuube|Kiruhura|Kiryandongo|Kisoro|Kitagwenda|Kitgum|Koboko|Kole|Kotido|Kumi|Kwania|Kween|Kyankwanzi|Kyegegwa|Kyenjojo|Kyotera|Lamwo|Lira|Luuka|Luwero|Lwengo|Lyantonde|Madi-Okollo|Manafwa|Maracha|Masaka|Masindi|Mayuge|Mbale|Mbarara|Mitooma|Mityana|Moroto|Moyo|Mpigi|Mubende|Mukono|Nabilatuk|Nakapiripirit|Nakaseke|Nakasongola|Namayingo|Namisindwa|Namutumba|Napak|Nebbi|Ngora|Ntoroko|Ntungamo|Nwoya|Obongi|Omoro|Otuke|Oyam|Pader|Pakwach|Pallisa|Rakai|Rubanda|Rubirizi|Rukiga|Rukungiri|Rwampara|Sembabule|Serere|Sheema|Sironko|Soroti|Terego|Tororo|Wakiso|Yumbe|Zombo"; // 135 districts (as of July 2020 structure, based on 2015 expansion) (https://en.wikipedia.org/wiki/Districts_of_Uganda)
aStates["Ukraine"] = "|Avtonomna Respublika Krym (Simferopol)|Cherkasy Oblast (Cherkasy)|Chernihiv Oblast (Chernihiv)|Chernivtsi Oblast (Chernivtsi)|Dnipropetrovsk Oblast (Dnipro)|Donetsk Oblast (Donetsk)|Ivano-Frankivsk Oblast (Ivano-Frankivsk)|Kharkiv Oblast (Kharkiv)|Kherson Oblast (Kherson)|Khmelnytskyi Oblast (Khmelnytskyi)|Kirovohrad Oblast (Kropyvnytskyi)|Kyiv|Kyiv Oblast (Kyiv)|Luhansk Oblast (Luhansk)|Lviv Oblast (Lviv)|Mykolaiv Oblast (Mykolaiv)|Odesa Oblast (Odesa)|Poltava Oblast (Poltava)|Rivne Oblast (Rivne)|Sevastopol|Sumy Oblast (Sumy)|Ternopil Oblast (Ternopil)|Vinnytsia Oblast (Vinnytsia)|Volyn Oblast (Lutsk)|Zakarpattia Oblast (Uzhhorod)|Zaporizhzhia Oblast (Zaporizhzhia)|Zhytomyr Oblast (Zhytomyr)" // 27 administrative divisions (24 oblasts + Crimea + Kyiv + Sevastopol, based on 2022 administrative changes) (https://en.wikipedia.org/wiki/Administrative_divisions_of_Ukraine)
aStates["United Arab Emirates"] = "|Abu Dhabi|Ajman|Dubai|Fujairah|Ras Al Khaimah|Sharjah|Umm Al Quwain"; // 7 emirates (https://en.wikipedia.org/wiki/Emirates_of_the_United_Arab_Emirates)
aStates["United Kingdom"] = "|Aberdeen City|Aberdeenshire|Anglesey|Angus|Argyll and Bute|Bedfordshire|Berkshire|Blaenau Gwent|Bridgend|Bristol|Buckinghamshire|Caerphilly|Cambridgeshire|Cardiff|Carmarthenshire|Ceredigion|Cheshire|Clackmannanshire|Conwy|Cornwall|County Antrim|County Armagh|County Down|County Fermanagh|County Londonderry|County Tyrone|Cumbria|Denbighshire|Derbyshire|Devon|Dorset|Dumfries and Galloway|Dundee|Durham|East Ayrshire|East Dunbartonshire|East Lothian|East Renfrewshire|East Riding of Yorkshire|East Sussex|Edinburgh|Essex|Falkirk|Fife|Flintshire|Glamorgan|Glasgow|Gloucestershire|Greater London|Greater Manchester|Gwynedd|Hampshire|Herefordshire|Hertfordshire|Highland|Inverclyde|Isle of Wight|Kent|Lancashire|Leicestershire|Lincolnshire|Merseyside|Merthyr Tydfil|Middlesex|Midlothian|Monmouthshire|Moray|Neath Port Talbot|Newport|Newport City|Norfolk|North Ayrshire|North Lanarkshire|North Yorkshire|Northamptonshire|Northumberland|Nottinghamshire|Orkney|Oxfordshire|Pembrokeshire|Perth and Kinross|Powys|Renfrewshire|Rhondda Cynon Taff|Rutland|Scottish Borders|Shetland Isles|Shropshire|Somerset|South Ayrshire|South Lanarkshire|South Yorkshire|Staffordshire|Stirlingshire|Suffolk|Surrey|Swansea|Torfaen|Tyne and Wear|Warwickshire|West Dunbartonshire|West Lothian|West Midlands|West Sussex|West Yorkshire|Western Isles|Wiltshire|Worcestershire|Wrexham"; // 109 administrative divisions (48 English counties, 6 Northern Irish counties, 32 Scottish council areas, 23 Welsh principal areas) (https://en.wikipedia.org/wiki/Administrative_geography_of_the_United_Kingdom)
aStates["United States Minor Outlying Islands"] = "|Baker Island|Howland Island|Jarvis Island|Johnston Atoll|Kingman Reef|Midway Atoll|Navassa Island|Palmyra Atoll|Wake Island"; // 9 islands and atolls (https://en.wikipedia.org/wiki/United_States_Minor_Outlying_Islands)
aStates["Uruguay"] = "|Artigas|Canelones|Cerro Largo|Colonia|Durazno|Flores|Florida|Lavalleja|Maldonado|Montevideo|Paysandu|Rio Negro|Rivera|Rocha|Salto|San Jose|Soriano|Tacuarembo|Treinta y Tres"; // 19 departments (https://en.wikipedia.org/wiki/Departments_of_Uruguay)
aStates["Uzbekistan"] = "|Andijon Wiloyati|Bukhoro Wiloyati|Farghona Wiloyati|Jizzakh Wiloyati|Khorazm Wiloyati (Urganch)|Namangan Wiloyati|Nawoiy Wiloyati|Qashqadaryo Wiloyati (Qarshi)|Qoraqalpoghiston (Nukus)|Samarqand Wiloyati|Sirdaryo Wiloyati (Guliston)|Surkhondaryo Wiloyati (Termiz)|Toshkent Shahri|Toshkent Wiloyati"; // 14 regions including autonomous republic and the capital city (https://en.wikipedia.org/wiki/Administrative_divisions_of_Uzbekistan)
aStates["Vanuatu"] = "|Malampa|Penama|Sanma|Shefa|Tafea|Torba"; // 6 provinces (https://en.wikipedia.org/wiki/Provinces_of_Vanuatu)
aStates["Venezuela"] = "|Amazonas|Anzoategui|Apure|Aragua|Barinas|Bolivar|Carabobo|Cojedes|Delta Amacuro|Dependencias Federales|Distrito Federal|Falcon|Guarico|Lara|Merida|Miranda|Monagas|Nueva Esparta|Portuguesa|Sucre|Tachira|Trujillo|Vargas|Yaracuy|Zulia"; // 25 divisions (23 states, 1 capital district, 1 federal dependencies; Vargas renamed La Guaira 2019) (https://en.wikipedia.org/wiki/States_of_Venezuela)
aStates["Vietnam"] = "|An Giang|Bắc Ninh|Cà Mau|Cần Thơ|Cao Bằng|Da Nang|Đắk Lắk|Điện Biên|Đồng Nai|Đồng Tháp|Gia Lai|Hà Tĩnh|Haiphong|Hanoi|Ho Chi Minh City|Huế|Hưng Yên|Khánh Hòa|Lai Châu|Lâm Đồng|Lạng Sơn|Lào Cai|Nghệ An|Ninh Bình|Phú Thọ|Quảng Ngãi|Quảng Ninh|Quảng Trị|Sơn La|Tây Ninh|Thái Nguyên|Thanh Hóa|Tuyên Quang|Vĩnh Long"; // 34 administrative divisions (28 provinces + 6 municipalities, based on 2025 governmental reforms) (https://en.wikipedia.org/wiki/Administrative_divisions_of_Vietnam)
aStates["Virgin Islands (British)"] = "|Anegada|Jost Van Dyke|Tortola|Virgin Gorda"; // 4 main islands (https://en.wikipedia.org/wiki/Administrative_divisions_of_the_British_Virgin_Islands)
aStates["Virgin Islands (U.S.)"] = "|Saint Croix|Saint John|Saint Thomas"; // 3 main islands (https://en.wikipedia.org/wiki/Administrative_divisions_of_the_United_States_Virgin_Islands)
aStates["Wallis and Futuna"] = "|Alo|Sigave|Uvea"; // 3 traditional kingdoms (https://en.wikipedia.org/wiki/Administrative_divisions_of_Wallis_and_Futuna)
aStates["Western Sahara"] = "|Aousserd|Boujdour|Dakhla|Es Semara|Laayoune|Oued ed Dahab"; // 6 provinces (https://en.wikipedia.org/wiki/Administrative_divisions_of_Western_Sahara)
aStates["Yemen"] = "|'Amran|Abyan|Aden|Al Bayda|Al Hudaydah|Al Jawf|Al Mahrah|Al Mahwit|Amanat Al Asimah|Dhale|Dhamar|Hadramaut|Hajjah|Ibb|Lahij|Ma'rib|Raymah|Sa'dah|Sana'a|Shabwah|Socotra|Taiz"; // 22 governorates (including 1 municipality) (https://en.wikipedia.org/wiki/Governorates_of_Yemen)
aStates["Zambia"] = "|Central|Copperbelt|Eastern|Luapula|Lusaka|Muchinga|North-Western|Northern|Southern|Western"; // 10 provinces (Muchinga created 2011) (https://en.wikipedia.org/wiki/Provinces_of_Zambia)
aStates["Zimbabwe"] = "|Bulawayo|Harare|Manicaland|Mashonaland Central|Mashonaland East|Mashonaland West|Masvingo|Matabeleland North|Matabeleland South|Midlands"; // 10 provinces (2 metropolitan provinces created 1997) (https://en.wikipedia.org/wiki/Provinces_of_Zimbabwe)
/* 
 * gArCountryInfo
 * (0) Country name
 * (1) Name length
 * (2) Number of states
 * (3) Max state length
 */
// build the ordered country list from aStates keys
gCountryList = Object.keys(aStates);
gLngNumberCountries = gCountryList.length;
// initialize country info array (filled in Fill_Country)
gArCountryInfo = new Array(gLngNumberCountries);
/* 
 * gArStateInfo[country][statenames][namelengths]
 * (0) Country
 * (1) States (Multi-sized Array)
 *    (0) name
 *    (1) nameLength
 */
gArStateInfo = new Array(gLngNumberCountries);

/* 
 * fInitgArStateInfo()
 * purpose: build gArStateInfo array
 * gArStateInfo[Country][States][1]
 *   (0) State name
 *   (1) State name length
 * gArStateInfo[i] is an array of state names
 * gArStateInfo[i][j]=state name, name length
 */
function fInitgArStateInfo() {
	var i = 0, j = 0, sStateName = "", iNumberOfStates = 0;
	var iMaxLength = 0, iLength = 0;
	var oldNumber = 0;
	var countryName = "";

	for (i = 0; i < gLngNumberCountries; i++) {
		// i is selected country index, get country name from derived list
		countryName = gCountryList[i];

		if (aStates[countryName]) {
			iNumberOfStates = aStates[countryName].split("|").length;
			gLngNumberStates = gLngNumberStates + iNumberOfStates;
			gArStateInfo[i] = new Array(iNumberOfStates);
			iMaxLength = 0;

			// Add the additional information
			for (j = 0; j < iNumberOfStates; j++) {
				sStateName = aStates[countryName].split("|")[j];
				iLength = sStateName.length;
				if (iLength > iMaxLength) {
					iMaxLength = iLength;
				}
				if (!gArStateInfo[i][j]) gArStateInfo[i][j] = new Array(2);
				gArStateInfo[i][j][0] = sStateName;
				gArStateInfo[i][j][1] = iLength;
			}
			gArCountryInfo[i][3] = parseInt(iMaxLength);
		}
	}
	Update_Globals();
	return;
}

/* 
 * Working on this one.
 * Fills in region from the arrays
 * 
 */
function xFillState() {
	var i = 0;
	var countryIndex = document.form1.country_name.selectedIndex;

	// reset region
	document.form1.region.options.length = 0;

	// get selected country
	gLngSelectedCountry = document.form1.country_name.options[countryIndex].text;

	// get number of states for selected country
	gLngNumberStates = gArCountryInfo[countryIndex][2];

	// update options in region
	for (i = 0; i < gLngNumberStates; i++) {
		document.form1.region.options[i] = new
			Option(gArStateInfo[countryIndex][i][0]);
	}
	gLngSelectedState =
		document.form1.region.options.selectedIndex;

	return;
}

/* 
 * FillStates() function works.
 * Fills region from aStates
 */
function Fill_States(current) {
	var i = 0, iNumStates = 0;

	// reset region
	document.form1.region.options.length = 0;
	// get selected country
	gLngSelectedCountry = document.form1.country_name.options[document.form1.country_name.selectedIndex].text;
	iNumStates = aStates[gLngSelectedCountry].split("|").length;

	// update the text boxes

	// fill the state combobox with the list of states
	for (i = 0; i < iNumStates; i++) {
		document.form1.region.options[i] = new
			Option(aStates[document.form1.country_name.options[document.form1.country_name.selectedIndex].text].split("|")[i]);

		sRegionName = document.form1.region.options[i];
		if (sRegionName.text == current) {
			document.form1.region.selectedIndex = i;
		}
	}

	return;
}

/*
 * FillCountry()
 * gArCountryInfo matrix holds the following information:
 * (0) Country name
 * (1) Name length
 * (2) Number of states
 * (3) Max state length
 */
function Fill_Country(current) {
	var i = 0;
	var sCountryName = "";

	// reset country_name.options
	document.form1.country_name.options.length = 0;
	// get number of countries from the string

	// ----------------------------------------------------
	// gArCountryInfo = new Array(gLngNumberCountries, 4);
	// ----------------------------------------------------
	for (i = 0; i < gLngNumberCountries; i++) {
		gArCountryInfo[i] = new Array(4);
	}

	for (i = 0; i < gLngNumberCountries; i++) {
		document.form1.country_name.options[i] = new Option(gCountryList[i]);
		sCountryName = document.form1.country_name.options[i];
		if (sCountryName.text == current) {
			document.form1.country_name.selectedIndex = i;
		}
		gArCountryInfo[i] =
			[sCountryName.text,
			parseInt(sCountryName.text.length),
			aStates[sCountryName.text] ? aStates[sCountryName.text].split("|").length : 0,
				0];
	}

	fInitgArStateInfo();

	return;
}

function Update_Globals() {
	gLngSelectedCountry = document.form1.country_name.options[document.form1.country_name.selectedIndex].text;
	gLngSelectedState = parseInt(document.form1.region.selectedIndex);
	return;
}

// @license-end
//-->
