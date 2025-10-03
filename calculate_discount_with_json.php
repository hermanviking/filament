<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;


// Embedded discount codes JSON data
$discountCodesJson = '[
  {
    "discountCode": "AKUNDER",
    "series": "102",
    "description": "A-kunder - Kenotek/Zvisser/40/20/  - 20% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:08:39.07",
    "lastModifiedDateTime": "2021-08-23T14:08:39.07",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 20,
        "discountPercent": 20,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "20"
      },
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "AKUNDER",
    "series": "103",
    "description": "A-Kunder- 10/ - 10% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:09:04.78",
    "lastModifiedDateTime": "2021-08-23T14:14:55.47",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 10,
        "discountPercent": 10,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "10"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "AKUNDER",
    "series": "104",
    "description": "A-kunder - Kenotek/Zvisser/40/20/  - 20% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:43:17.227",
    "lastModifiedDateTime": "2024-10-08T12:43:17.227",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "AKUNDER",
    "series": "105",
    "description": "A-Kunder- 10/ - 10% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:43:17.383",
    "lastModifiedDateTime": "2024-10-08T12:43:17.383",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "B2B",
    "series": "B2B0001",
    "description": "Rabatt EVO pads",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:38.737",
    "lastModifiedDateTime": "2024-10-08T12:48:38.737",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "BKUNDER",
    "series": "201",
    "description": "B-Kunder - Kenotek/Zvisser/40/20/  - 15% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:09:51.95",
    "lastModifiedDateTime": "2021-08-23T14:15:18.42",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 15,
        "discountPercent": 15,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "B"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "20"
      },
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "BKUNDER",
    "series": "202",
    "description": "B-Kunder- 10/ - 10% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:10:18.647",
    "lastModifiedDateTime": "2021-08-23T14:15:31.497",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 10,
        "discountPercent": 10,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "B"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "10"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "BKUNDER",
    "series": "203",
    "description": "B-Kunder - Kenotek/Zvisser/40/20/  - 15% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:38.82",
    "lastModifiedDateTime": "2024-10-08T12:48:38.82",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "BKUNDER",
    "series": "204",
    "description": "B-Kunder- 10/ - 10% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:38.907",
    "lastModifiedDateTime": "2024-10-08T12:48:38.907",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "CKUNDER",
    "series": "301",
    "description": "C-Kunder - 10/ - 10%",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:11:14.88",
    "lastModifiedDateTime": "2024-10-08T12:48:38.997",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 10,
        "discountPercent": 10,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "C"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "10"
      },
      {
        "priceClassId": "20"
      },
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "FORHANDL",
    "series": "503",
    "description": "Forhandlere - Kenotek/Zvisser/40/ - 40% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:06:20.56",
    "lastModifiedDateTime": "2021-08-23T14:06:20.56",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 40,
        "discountPercent": 40,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "FORHANDLER"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "FORHANDL",
    "series": "504",
    "description": "Forhandlere - 20/ - 20% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:06:52.89",
    "lastModifiedDateTime": "2021-08-23T14:07:06.543",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 20,
        "discountPercent": 20,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "FORHANDLER"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "20"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "FORHANDL",
    "series": "505",
    "description": "Forhandlere - 10/ - 10% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:07:33.06",
    "lastModifiedDateTime": "2021-08-23T14:07:33.06",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 10,
        "discountPercent": 10,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "FORHANDLER"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "10"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "FORHANDL",
    "series": "506",
    "description": "Forhandlere",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-11-11T00:00:00",
    "lastUpdateDate": "2024-11-11T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 3,
    "createdDateTime": "2024-11-11T17:27:54.217",
    "lastModifiedDateTime": "2024-11-11T17:28:08.63",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 1,
        "quantityTo": 12,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 20,
        "discountPercent": 20,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2024-11-11T00:00:00"
      },
      {
        "lineNbr": 2,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 12,
        "quantityTo": 600,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 40,
        "discountPercent": 40,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2024-11-11T00:00:00"
      },
      {
        "lineNbr": 3,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 600,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 50,
        "discountPercent": 50,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2024-11-11T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "FORHANDLER"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "KENO1L"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "FORHANDLER",
    "series": "FORHANDLER",
    "description": "Forhandlere - Kenotek/Zvisser/40/ - 40% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:39.073",
    "lastModifiedDateTime": "2024-10-08T12:48:39.073",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "GUMPEN",
    "series": "GUMPEN0001",
    "description": "Gumpen Auto 35% rabatt /Kenotek-Zvizzer-40",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:39.69",
    "lastModifiedDateTime": "2024-10-08T12:48:39.69",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "GUMPEN",
    "series": "GUMPEN0002",
    "description": "Gumpen Auto 20% rabatt - 20",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:39.783",
    "lastModifiedDateTime": "2024-10-08T12:48:39.783",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "GUMPEN",
    "series": "GUMPEN0003",
    "description": "Gumpen Auto 30% rabatt - 30",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:39.89",
    "lastModifiedDateTime": "2024-10-08T12:48:39.89",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "KAMPANJE",
    "series": "KAMPANJE00",
    "description": "Kampanje",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:40.957",
    "lastModifiedDateTime": "2024-10-08T12:48:40.957",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "RENTAL",
    "series": "RENTAL0001",
    "description": "Rental Group",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.04",
    "lastModifiedDateTime": "2024-10-08T12:48:41.04",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STANGELAND",
    "series": "1",
    "description": "Stangeland",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.13",
    "lastModifiedDateTime": "2024-10-08T12:48:41.13",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STOR30",
    "series": "STOR300001",
    "description": "Storkunde 30%",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.203",
    "lastModifiedDateTime": "2024-10-08T12:48:41.203",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STOR30",
    "series": "STOR300002",
    "description": "Storkunde 30%",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.283",
    "lastModifiedDateTime": "2024-10-08T12:48:41.283",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STOR30",
    "series": "STOR300003",
    "description": "Storkunde 30%",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.367",
    "lastModifiedDateTime": "2024-10-08T12:48:41.367",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STORKUNDE",
    "series": "401",
    "description": "Storkunde - Kenotek/Zvisser/40/ - 25% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:12:11.083",
    "lastModifiedDateTime": "2021-08-23T14:15:46.77",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 25,
        "discountPercent": 25,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "STOR"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STORKUNDE",
    "series": "402",
    "description": "Storkunde - 20 - 20% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:13:00.68",
    "lastModifiedDateTime": "2021-08-23T14:13:00.68",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 20,
        "discountPercent": 20,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "STOR"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "20"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STORKUNDE",
    "series": "403",
    "description": "Storkunde - 10/ - 10% rabatt",
    "discountBy": "P",
    "breakBy": "A",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2021-08-23T00:00:00",
    "lastUpdateDate": "2021-08-23T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 1,
    "createdDateTime": "2021-08-23T14:13:28.533",
    "lastModifiedDateTime": "2021-08-23T14:13:28.533",
    "discountBreakpoints": [
      {
        "lineNbr": 1,
        "active": true,
        "breakAmount": 0,
        "lastBreakAmount": 0,
        "breakQuantity": 0,
        "lastBreakQuantity": 0,
        "pendingBreakQuantity": 0,
        "discountAmount": 10,
        "discountPercent": 10,
        "lastDiscountAmount": 0,
        "lastDiscountPercent": 0,
        "freeItemQty": 0,
        "lastFreeItemQty": 0,
        "pendingFreeItemQty": 0,
        "effectiveDate": "2021-08-23T00:00:00"
      }
    ],
    "customerPriceClasses": [
      {
        "priceClassId": "STOR"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "10"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STORKUNDE",
    "series": "404",
    "description": "Storkunde - Kenotek/Zvisser/40/ - 25% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.45",
    "lastModifiedDateTime": "2024-10-08T12:48:41.45",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STORKUNDE",
    "series": "405",
    "description": "Storkunde - 20 - 20% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.55",
    "lastModifiedDateTime": "2024-10-08T12:48:41.55",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  },
  {
    "discountCode": "STORKUNDE",
    "series": "406",
    "description": "Storkunde - 10/ - 10% rabatt",
    "discountBy": "P",
    "breakBy": "Q",
    "promotional": false,
    "active": true,
    "prorateDiscount": false,
    "effectiveDate": "2024-10-08T00:00:00",
    "freeItem": 0,
    "pendingFreeItem": 0,
    "lastFreeItem": 0,
    "lineCntr": 0,
    "createdDateTime": "2024-10-08T12:48:41.86",
    "lastModifiedDateTime": "2024-10-08T12:48:41.86",
    "customerPriceClasses": [
      {
        "priceClassId": "A"
      },
      {
        "priceClassId": "rg"
      }
    ],
    "itemPriceClasses": [
      {
        "priceClassId": "40"
      },
      {
        "priceClassId": "keno1l"
      },
      {
        "priceClassId": "KENO200L"
      },
      {
        "priceClassId": "KENO20L"
      },
      {
        "priceClassId": "keno5l"
      },
      {
        "priceClassId": "KENOTEK"
      },
      {
        "priceClassId": "ZVIZZER"
      }
    ],
    "metadata": {
      "totalCount": 30,
      "maxPageSize": 1000
    }
  }
]';


$discountsPath = storage_path('discounts.json');
if (!file_exists($discountsPath)) {
    Log::error('Discount file not found at: ' . $discountsPath);
    return;
}

$discountCodes = json_decode(file_get_contents($discountsPath), true);
if (!$discountCodes) {
    Log::error('Failed to parse discounts JSON: ' . json_last_error_msg());
    return;
}

if ($discountCodes === null) {
    die("Error decoding JSON. Please check the format of your embedded JSON.\n");
}

/**
 * Function to get the best discount for a customer and product.
 * 
 * @param string $customerPriceClassId The customer price class ID.
 * @param string $itemPriceClassId The item price class ID.
 * @param int $quantity The quantity of the product.
 * @param array $discountCodes The list of discount codes from the JSON.
 * 
 * @return float|int The discount percentage if found, otherwise 0.
 */
function getDiscountForCustomerAndProduct($customerPriceClassId, $itemPriceClassId, $quantity, $discountCodes) {
    $bestDiscount = 0;

    foreach ($discountCodes as $discountCode) {
        // Check if the discount is active
        if (!$discountCode['active']) {
            continue;
        }

        // Check if customerPriceClasses match
        $customerMatch = false;
        foreach ($discountCode['customerPriceClasses'] as $customerClass) {
            if ($customerClass['priceClassId'] === $customerPriceClassId) {
                $customerMatch = true;
                break;
            }
        }

        // Check if itemPriceClasses match
        $itemMatch = false;
        foreach ($discountCode['itemPriceClasses'] as $itemClass) {
            if ($itemClass['priceClassId'] === $itemPriceClassId) {
                $itemMatch = true;
                break;
            }
        }

        // If both customer and item price classes match, check quantity-based discount
        if ($customerMatch && $itemMatch) {
            foreach ($discountCode['discountBreakpoints'] as $breakpoint) {
                if ($breakpoint['active'] && $quantity >= $breakpoint['breakQuantity']) {
                    $discountPercent = $breakpoint['discountPercent'] ?? 0;

                    // Use the maximum discount value if multiple discounts apply
                    if ($discountPercent > $bestDiscount) {
                        $bestDiscount = $discountPercent;
                    }
                }
            }
        }
    }

    return $bestDiscount;
}

// Testing the function with sample customer and product price class IDs
$customerPriceClassId = "FORHANDLER";  // Replace with actual customer price class from Visma API
$itemPriceClassId = "KENO1L"; // Replace with actual item price class from Visma API
$quantity = 601;  // Replace with the quantity of the product being purchased

$discount = getDiscountForCustomerAndProduct($customerPriceClassId, $itemPriceClassId, $quantity, $discountCodes);

echo "The applicable discount for quantity $quantity is: " . $discount . "%\n";

?>
