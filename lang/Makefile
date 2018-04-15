PHP_FILES := $(wildcard ../views/*.php) $(wildcard ../inc/*.php)
DOMAIN    := login-logger
PO_FILES  := $(wildcard $(DOMAIN)-*.po)
MO_FILES  := $(patsubst %.po,%.mo,$(PO_FILES))

POT       := $(DOMAIN).pot

XGETTEXT  := xgettext
MSGFMT    := msgfmt
MSGMERGE  := msgmerge

XGETTEXT_OPTIONS := --default-domain=$(DOMAIN) --language=php \
	--keyword=__ \
	--keyword=_e \
	--keyword=_x:1,2c \
	--keyword=_ex:1,2c \
	--keyword=_n:1,2 \
	--keyword=_nx:1,2,4c \
	--keyword=_n_noop:1,2 \
	--keyword=_nx_noop:1,2,3c \
	--keyword=esc_html__ \
	--keyword=esc_html_e \
	--keyword=esc_html_x:1,2c \
	--keyword=esc_attr__ \
	--keyword=esc_attr_e \
	--keyword=esc_attr_x:1,2c \
	--from-code=UTF-8 --omit-header

.PHONY: all clean

all: $(POT) $(MO_FILES)

$(MO_FILES): %.mo: %.po
	$(MSGFMT) -o$@ $<

$(PO_FILES): %: $(POT)
	$(MSGMERGE) -U "$@" $(POT)
	touch "$@"

$(POT): $(PHP_FILES)
	$(XGETTEXT) $(XGETTEXT_OPTIONS) $(PHP_FILES) --output="$@"

clean:
	-rm -f $(POT) $(MO_FILES)
