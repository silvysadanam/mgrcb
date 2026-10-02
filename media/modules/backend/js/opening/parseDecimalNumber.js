var parseDecimalNumber = function(value, inOptions, enforceGroupSize) {
    var thousandsSeparator = ",";
    var decimalSeparator = ".";
    /*if(top.menu.jsonLocale.hasOwnProperty('thousands_separator'))
    {
        thousandsSeparator = top.menu.jsonLocale.thousands_separator.symbol;
    }
    if(top.menu.jsonLocale.hasOwnProperty('decimal_separator'))
    {
        decimalSeparator = top.menu.jsonLocale.decimal_separator.symbol;
    }*/
    var options = {
      thousands: thousandsSeparator,
      decimal: decimalSeparator
    };
    var patterns = [];
    var decimal, fractionPart, groupMinSize, integerPart, number, pattern, patternIndex, result, thousands;
    if (enforceGroupSize == null) {
      enforceGroupSize = true;
    }
    if (typeof inOptions === 'string') {
      if (inOptions.length !== 2) {
        throw {
          name: 'ArgumentException',
          message: 'The format for string options is \'<thousands><decimal>\' (exactly two characters)'
        };
      }
      thousands = inOptions[0], decimal = inOptions[1];
    } else if (Array.isArray(inOptions)) {
      if (inOptions.length !== 2) {
        throw {
          name: 'ArgumentException',
          message: 'The format for array options is [\'<thousands>\',\'[<decimal>\'] (exactly two elements)'
        };
      }
      thousands = inOptions[0], decimal = inOptions[1];
    } else {
      thousands = (inOptions != null ? inOptions.thousands : void 0) || (inOptions != null ? inOptions.group : void 0) || options.thousands;
      decimal = (inOptions != null ? inOptions.decimal : void 0) || options.decimal;
    }
    patternIndex = "" + thousands + decimal + enforceGroupSize;
    pattern = patterns[patternIndex];
    if (pattern == null) {
      groupMinSize = enforceGroupSize ? 3 : 1;
      pattern = patterns[patternIndex] = new RegExp("^\\s*([+\-]?(?:(?:\\d{1,3}(?:\\" + thousands + "\\d{" + groupMinSize + ",3})+)|\\d*))(?:\\" + decimal + "(\\d*))?\\s*$");
    }
    result = value.match(pattern);
    if (!((result != null) && result.length === 3)) {
      return value;
    }
    integerPart = result[1].replace(new RegExp("\\" + thousands, 'g'), '');
    fractionPart = result[2];
    number = parseFloat(integerPart + "." + fractionPart);
    return number;
  };